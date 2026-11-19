<?php
declare(strict_types=1);
//DB接続を取得する関数
function getDatabaseConnection(): PDO
{
    static $connection = null;
    //既に接続が確立されている場合はそれを返す
    if ($connection instanceof PDO) {
        return $connection;
    }
    //PDO SQLite拡張がロードされていない場合は例外を投げる
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('The PHP pdo_sqlite extension is required.');
    }

    //プロジェクトのルートディレクトリを取得
    $projectRoot = dirname(__DIR__);
    $databaseDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'database';
    //データベースディレクトリが存在しない場合は作成
    if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
        throw new RuntimeException('Could not create the database directory.');
    }

    //DBの設定
    $databasePath = $databaseDirectory . DIRECTORY_SEPARATOR . 'alertcast.sqlite';
    $databaseConnection = new PDO('sqlite:' . $databasePath);
    $databaseConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $databaseConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $databaseConnection->exec('PRAGMA foreign_keys = ON');
    $databaseConnection->exec('PRAGMA busy_timeout = 5000');
    //DBのマイグレーションを適用
    applyDatabaseMigrations($databaseConnection, $projectRoot);
    $connection = $databaseConnection;
    return $connection;
}
//DBのマイグレーション
function applyDatabaseMigrations(PDO $connection, string $projectRoot): void
{
    //履歴テーブルの作成
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    //スケジュールテーブルの作成
    $migrationVersion = '001_create_schedules.sql';
    $migrationPath = $projectRoot
        . DIRECTORY_SEPARATOR . 'database'
        . DIRECTORY_SEPARATOR . 'migrations'
        . DIRECTORY_SEPARATOR . $migrationVersion;
    $connection->beginTransaction();
    //マイグレーションの適用
    try {
        //マイグレーションが適用済みか確認
        $check = $connection->prepare(
            'SELECT 1 FROM schema_migrations WHERE version = :version'
        );
        $check->execute(['version' => $migrationVersion]);
        //適用済みでなければマイグレーションを実行
        if ($check->fetchColumn() === false) {
            $migrationSql = file_get_contents($migrationPath);
            //マイグレーションSQLが読み込めなかった場合は例外を投げる
            if ($migrationSql === false) {
                throw new RuntimeException('Could not read the schedule database migration.');
            }
            //マイグレーションSQLを実行
            $connection->exec($migrationSql);
            //マイグレーションの適用履歴を記録
            $record = $connection->prepare(
                'INSERT INTO schema_migrations (version) VALUES (:version)'
            );
            //マイグレーションの適用履歴を記録
            $record->execute(['version' => $migrationVersion]);
        }
        //トランザクションをコミット
        $connection->commit();
        //トランザクションをロールバックして例外を再スロー
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}
        