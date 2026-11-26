


# 機能
- SQLiteデータベースへの接続を管理する
- 必要なフォルダ・DBファイルを準備する
- 初回起動時にテーブルを自動作成する
- マイグレーション履歴を管理する
# 説明
## データベース接続の取得
	- function getDatabaseConnection(): PDO
	- データベースの接続を取得
	- 接続済みの場合は再利用する
## SQLite利用可否の確認
	- if (!extension_loaded('pdo_sqlite')) {...}
	- PDO SQLite拡張の有無を確認する
	- 利用できない場合は例外を発生させる
## データベース保存用フォルダの作成
	- $databaseDirectory = ...
	if (!is_dir(...)) {
		mkdir(...);
	}
	- databaseフォルダの存在を確認する
	- 存在しない場合は作成する
## SQLiteファイルの作成・接続
	- $databaseConnection =
	new PDO('sqlite:' . $databasePath);
	-SQLiteデータベースへ接続する
	-データベースファイルを利用する
## PDOの設定
	- $databaseConnection->setAttribute(...);
	- SQLエラーを例外として扱う
	- SELECT結果を連想配列で取得する
## SQLiteの動作設定
	- $databaseConnection->exec(
	'PRAGMA foreign_keys = ON'
	);
	$databaseConnection->exec(
	'PRAGMA busy_timeout = 5000'
	);
	- 外部キー制約を有効化する
	- ロック時の待機時間を設定する
## マイグレーションの実行
	- applyDatabaseMigrations(
	$databaseConnection,
	$projectRoot
	);
	- テーブル作成処理を実行
	- データベース構造を最新状態へ更新する
## マイグレーション機能
	function applyDatabaseMigrations(...)
		データベース更新処理を管理する
		SQLファイルを実行する
## マイグレーションファイルの一覧取得
	glob($migrationDirectory . DIRECTORY_SEPARATOR . '*.sql')
	sort($migrationPaths, SORT_STRING)
	database/migrations のSQLファイルをすべて取得する
	ファイル名の番号順（001, 002, ...）に並べて、1ファイルずつ適用する
	バージョン名にはファイル名（例：001_create_schedules.sql）を使う
## マイグレーション履歴テーブルの作成
	CREATE TABLE IF NOT EXISTS schema_migrations
	実行済みマイグレーションを実行する
	重複実行を防ぐ
## 実行済みマイグレーションの確認
	SELECT 1
	FROM schema_migrations
	WHERE version = :version
	SQLファイルが実行済みか確認する
## マイグレーションSQLの読込
	$migrationSql =
	 file_get_contents($migrationPath);
	 SQLファイルの内容を取得する
## マイグレーションSQLの実行
	$connection->exec($migrationSql);
	テーブル作成や更新処理を実行する
## マイグレーション履歴の記録
	INSERT INTO schema_migrations (...)
	実行済み履歴を保存する
## トランザクション管理
	beginTransaction()
	commit()
	rollBack()
	DB更新処理をまとめて管理する
	エラー時は変更を取り消す
## 接続の再利用
	static $connection = null;
	PDO接続を保持する
	同じ接続を使いまわして効率化する
# src
```php
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

    //migrationsフォルダのSQLファイルを番号順（ファイル名順）に取得
    $migrationDirectory = $projectRoot
        . DIRECTORY_SEPARATOR . 'database'
        . DIRECTORY_SEPARATOR . 'migrations';
    $migrationPaths = glob($migrationDirectory . DIRECTORY_SEPARATOR . '*.sql');
    if ($migrationPaths === false) {
        throw new RuntimeException('Could not list the database migrations.');
    }
    sort($migrationPaths, SORT_STRING);

    //適用済みか確認するSQLと、適用履歴を記録するSQLを準備
    $check = $connection->prepare(
        'SELECT 1 FROM schema_migrations WHERE version = :version'
    );
    $record = $connection->prepare(
        'INSERT INTO schema_migrations (version) VALUES (:version)'
    );

    //マイグレーションを1ファイルずつ適用
    foreach ($migrationPaths as $migrationPath) {
        //バージョン名はファイル名（例：001_create_schedules.sql）
        $migrationVersion = basename($migrationPath);
        $connection->beginTransaction();
        try {
            //マイグレーションが適用済みか確認
            $check->execute(['version' => $migrationVersion]);
            $isApplied = $check->fetchColumn() !== false;
            $check->closeCursor();
            //適用済みでなければマイグレーションを実行
            if (!$isApplied) {
                $migrationSql = file_get_contents($migrationPath);
                //マイグレーションSQLが読み込めなかった場合は例外を投げる
                if ($migrationSql === false) {
                    throw new RuntimeException('Could not read the database migration: ' . $migrationVersion);
                }
                //マイグレーションSQLを実行
                $connection->exec($migrationSql);
                //マイグレーションの適用履歴を記録
                $record->execute(['version' => $migrationVersion]);
            }
            //トランザクションをコミット
            $connection->commit();
        } catch (Throwable $exception) {
            //トランザクションをロールバックして例外を再スロー
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }
}
```
