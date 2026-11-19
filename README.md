# お知らせ放送

個人向けのスケジュール管理アプリです。
機能要件は [docs/ProjectMemo.md](docs/ProjectMemo.md) を参照してください。

## ディレクトリ構成
```text
public/                  ブラウザーに公開するページとアセット
  index.html
  schedule-edit.html
  schedule-create.php    時間指定予定の登録処理
  show-table.html
  assets/
    css/
src/                     PHPのアプリケーション処理
  Data.php               SQLite接続と初回マイグレーション
docs/                    要件・設計メモ
```

## 現在の状態

時間指定予定を `schedule-edit.html` から登録し、SQLiteに保存できます。予定の表示・変更・削除や、終日予定・期限予定の登録は未実装です。

`src/Data.php` の `getDatabaseConnection()` をPHPから呼ぶと、`database/alertcast.sqlite` が作成され、`database/migrations/` の初回マイグレーションが適用されます。PHPの `pdo_sqlite` 拡張が必要です。`public/index.html` を直接開いただけではデータベースは作成されません。

## 実行環境の準備（Windows）

PHPをインストールし、コマンドプロンプトまたはPowerShellから `php` コマンドを実行できるようにしてください。PHPの設定ファイルとSQLite拡張は次の手順で確認できます。

1. `php --ini` を実行し、`Loaded Configuration File` に表示された `php.ini` を開きます。
2. `extension_dir` がPHPの `ext` フォルダーを指していることを確認します。
3. `;extension=pdo_sqlite` のように行頭に `;` がある場合は外して、`extension=pdo_sqlite` にします。PHPの `ext` フォルダーに `php_pdo_sqlite.dll` があることも確認してください。
4. PHPを使っているターミナルやWebサーバーを再起動します。
5. `php -m` を実行し、一覧に `pdo_sqlite` があることを確認します。さらに `php -r "var_export(PDO::getAvailableDrivers());"` の出力に `sqlite` があれば、PDOからSQLiteを利用できます。

拡張が見つからない場合は、実行中のPHPとバージョン・アーキテクチャなどが一致する配布物を使用してください。Webサーバー経由で実行する場合は、CLIとは別の `php.ini` が使われていないかも確認してください。

## 今後の追加先

- データベースの変更SQLは `database/migrations/`
- テストは `tests/`
- 画面の動作を担うJavaScriptは `public/assets/js/`
