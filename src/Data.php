<?php
declare(strict_types=1);
// データベース接続を取得する関数
function getDatabaseConnection(): PDO
{
    static $connection = null;
    // データベース接続の確認
    if ($connection instanceof PDO) {
        return $connection;
    }
    // SQLite拡張モジュールの確認
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('The PHP pdo_sqlite extension is required.');
    }

    // データベースディレクトリの作成
    $projectRoot = dirname(__DIR__);
    $databaseDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'database';
    // ディレクトリが存在しない場合は作成する
    if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
        throw new RuntimeException('Could not create the database directory.');
    }

    // データベースファイルのパスを設定
    $databasePath = $databaseDirectory . DIRECTORY_SEPARATOR . 'alertcast.sqlite';
    // データベース接続の作成
    $databaseConnection = new PDO('sqlite:' . $databasePath);
    // データベース接続の設定
    $databaseConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // デフォルトのフェッチモードを連想配列に設定
    $databaseConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // 外部キー制約を有効にする
    $databaseConnection->exec('PRAGMA foreign_keys = ON');
    // ビジー状態のタイムアウトを設定
    $databaseConnection->exec('PRAGMA busy_timeout = 5000');
    // データベースマイグレーションの適用
    applyDatabaseMigrations($databaseConnection, $projectRoot);
    // データベース接続を返す
    $connection = $databaseConnection;
    return $connection;
}
// データベースマイグレーションを適用する関数
function applyDatabaseMigrations(PDO $connection, string $projectRoot): void
{
    // マイグレーションテーブルの作成
    $connection->exec(
        // スキーママイグレーションテーブルの作成
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    // マイグレーションのバージョンを指定
    $migrationVersion = '001_create_schedules.sql';
    // マイグレーションファイルのパスを設定
    $migrationPath = $projectRoot
        // データベースマイグレーションディレクトリ
        . DIRECTORY_SEPARATOR . 'database'
        // マイグレーションディレクトリ
        . DIRECTORY_SEPARATOR . 'migrations'
        // マイグレーションファイルのバージョン
        . DIRECTORY_SEPARATOR . $migrationVersion;
    // トランザクションを開始
    $connection->beginTransaction();
    // マイグレーションの適用を試みる
    try {
        // マイグレーションが既に適用されているか確認
        $check = $connection->prepare(
            'SELECT 1 FROM schema_migrations WHERE version = :version'
        );
        // マイグレーションのバージョンを指定して実行
        $check->execute(['version' => $migrationVersion]);
        // マイグレーションが適用されていない場合は適用する
        if ($check->fetchColumn() === false) {
            // マイグレーションSQLを読み込む
            $migrationSql = file_get_contents($migrationPath);
            // マイグレーションSQLが読み込めなかった場合は例外を投げる
            if ($migrationSql === false) {
                throw new RuntimeException('Could not read the schedule database migration.');
            }
            // マイグレーションSQLを実行
            $connection->exec($migrationSql);
            // マイグレーションのバージョンを記録
            $record = $connection->prepare(
                'INSERT INTO schema_migrations (version) VALUES (:version)'
            );
            $record->execute(['version' => $migrationVersion]);
        }
        // トランザクションをコミット
        $connection->commit();
    } catch (Throwable $exception) {
        // トランザクションをロールバック
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        // 例外を再スロー
        throw $exception;
    }
}
