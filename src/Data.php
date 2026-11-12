<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('The PHP pdo_sqlite extension is required.');
    }

    $projectRoot = dirname(__DIR__);
    $databaseDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'database';
    if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
        throw new RuntimeException('Could not create the database directory.');
    }

    $databasePath = $databaseDirectory . DIRECTORY_SEPARATOR . 'alertcast.sqlite';
    $databaseConnection = new PDO('sqlite:' . $databasePath);
    $databaseConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $databaseConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $databaseConnection->exec('PRAGMA foreign_keys = ON');
    $databaseConnection->exec('PRAGMA busy_timeout = 5000');

    applyDatabaseMigrations($databaseConnection, $projectRoot);

    $connection = $databaseConnection;
    return $connection;
}

function applyDatabaseMigrations(PDO $connection, string $projectRoot): void
{
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $migrationVersion = '001_create_schedules.sql';
    $migrationPath = $projectRoot
        . DIRECTORY_SEPARATOR . 'database'
        . DIRECTORY_SEPARATOR . 'migrations'
        . DIRECTORY_SEPARATOR . $migrationVersion;

    $connection->beginTransaction();
    try {
        $check = $connection->prepare(
            'SELECT 1 FROM schema_migrations WHERE version = :version'
        );
        $check->execute(['version' => $migrationVersion]);

        if ($check->fetchColumn() === false) {
            $migrationSql = file_get_contents($migrationPath);
            if ($migrationSql === false) {
                throw new RuntimeException('Could not read the schedule database migration.');
            }

            $connection->exec($migrationSql);

            $record = $connection->prepare(
                'INSERT INTO schema_migrations (version) VALUES (:version)'
            );
            $record->execute(['version' => $migrationVersion]);
        }

        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    }
}
