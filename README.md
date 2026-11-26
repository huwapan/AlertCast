# お知らせ放送

個人向けのスケジュール管理アプリです。
機能要件は [docs/ProjectMemo.md](docs/ProjectMemo.md) を参照してください。

## ディレクトリ構成
```text
public/                  ブラウザーに公開するページとアセット
  index.html
  schedule-edit.html
  register-schedule.php  予定登録のHTTPエンドポイント
  delete-schedule.php    予定削除のHTTPエンドポイント
  show-table.php         優先度順の予定一覧（削除ボタン付き）
  assets/
    css/
src/                     PHPのアプリケーション処理
  Data.php               SQLite接続とマイグレーションの適用
  view.php               HTMLエスケープとメッセージ画面の共通関数
  schedule-create.php    時間指定予定の入力検証と保存
  schedule-delete.php    予定の論理削除
database/migrations/     データベースの変更SQL（番号順に自動で適用）
docs/                    要件・設計メモ
planMD/                  機能ごとの実装計画書
```

## 現在の状態

時間指定予定を `schedule-edit.html` から登録し、SQLiteに保存できます。登録済みの時間指定予定は、優先度順で `show-table.php` に表示されます。一覧の削除ボタンで予定を削除できます。削除は論理削除で、行は残したまま `deleted_at` に削除日時を記録し、一覧には表示しません。予定の変更・復元や、終日予定・期限予定の登録は未実装です。

`src/Data.php` の `getDatabaseConnection()` をPHPから呼ぶと、`database/alertcast.sqlite` が作成され、`database/migrations/` のSQLファイルのうち未適用のものがファイル名の番号順に適用されます。適用済みのものは `schema_migrations` テーブルに記録されます。PHPの `pdo_sqlite` 拡張が必要です。`public/index.html` を直接開いただけではデータベースは作成されません。

## ローカルでの起動

プロジェクトのルートディレクトリで次のコマンドを実行し、`http://127.0.0.1:8000` を開きます。

```powershell
php -S 127.0.0.1:8000 -t public
```

## 実行環境の準備（Windows）

PHPをインストールし、コマンドプロンプトまたはPowerShellから `php` コマンドを実行できるようにしてください。PHPの設定ファイルとSQLite拡張は次の手順で確認できます。

1. `php --ini` を実行し、`Loaded Configuration File` に表示された `php.ini` を開きます。
2. `extension_dir` がPHPの `ext` フォルダーを指していることを確認します。
3. `;extension=pdo_sqlite` のように行頭に `;` がある場合は外して、`extension=pdo_sqlite` にします。PHPの `ext` フォルダーに `php_pdo_sqlite.dll` があることも確認してください。
4. PHPを使っているターミナルやWebサーバーを再起動します。
5. `php -m` を実行し、一覧に `pdo_sqlite` があることを確認します。さらに `php -r "var_export(PDO::getAvailableDrivers());"` の出力に `sqlite` があれば、PDOからSQLiteを利用できます。

拡張が見つからない場合は、実行中のPHPとバージョン・アーキテクチャなどが一致する配布物を使用してください。Webサーバー経由で実行する場合は、CLIとは別の `php.ini` が使われていないかも確認してください。

## 今後の追加先

- データベースの変更SQLは `database/migrations/`（`003_...sql` のように次の番号を付ける）
- 機能ごとの実装計画書は `planMD/`（`002_...md` のように次の番号を付ける）
- テストは `tests/`
- 画面の動作を担うJavaScriptは `public/assets/js/`
