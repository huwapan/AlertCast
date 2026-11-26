# お知らせ放送

個人向けのスケジュール管理アプリです。
機能要件は [docs/ProjectMemo.md](docs/ProjectMemo.md) を参照してください。

## ディレクトリ構成
```text
public/                  ブラウザーに公開するページとアセット
  index.php              ホーム（今日の予定、完了通知、期限を超過した予定）
  schedule-edit.html
  register-schedule.php  予定登録のHTTPエンドポイント
  complete-schedule.php  予定完了のHTTPエンドポイント
  delete-schedule-form.php  予定の削除フォーム（schedule-edit.html から移動）
  delete-schedule.php    予定削除のHTTPエンドポイント
  update-schedule-form.php  変更する予定を選ぶ一覧と変更フォーム（schedule-edit.html から移動）
  update-schedule.php    予定変更のHTTPエンドポイント
  show-table.php         優先度順の予定一覧（見るためのページ）
  assets/
    css/
src/                     PHPのアプリケーション処理
  Data.php               SQLite接続とマイグレーションの適用
  view.php               HTMLエスケープとメッセージ画面の共通関数
  schedule-input.php     時間指定予定の入力検証（登録と変更で共通）
  schedule-create.php    時間指定予定の保存
  schedule-delete.php    予定の論理削除
  schedule-update.php    時間指定予定の変更
  schedule-complete.php  予定の完了の記録
  schedule-reminder.php  ホームに出す予定（今日の予定・完了通知・期限超過）の取得
database/migrations/     データベースの変更SQL（番号順に自動で適用）
docs/                    要件・設計メモ
planMD/                  機能ごとの実装計画書
```

## 現在の状態

時間指定予定を `schedule-edit.html` から登録し、SQLiteに保存できます。登録済みの時間指定予定は、優先度順で `show-table.php` に表示され、完了したかどうかも分かります（見るためのページで、操作はできません）。ホーム（`index.php`）には、今日にかかる予定が開始日時の早い順に表示されます。終了日時を過ぎてから1日以内で、まだ完了していない予定は「終わった予定があります。完了しましたか？」と通知されます。終了日時から1日たっても完了していない予定は「期限を超過した予定があります。忘れていませんか？」と表示されます。どちらも「完了」ボタンを押すと `completed_at` に完了日時を記録します。予定を削除するときは、`schedule-edit.html` の「予定の破棄フォーム」から削除フォーム（`delete-schedule-form.php`）に移動し、予定を選んで削除します。削除は論理削除で、行は残したまま `deleted_at` に削除日時を記録し、一覧と削除フォームには表示しません。予定を変更するときは、`schedule-edit.html` の「予定の変更フォーム」から変更ページ（`update-schedule-form.php`）に移動し、予定を選んでタイトル・日時・優先度を直します。完了済みの予定も変更でき、完了の記録はそのまま残ります。予定の復元や、終日予定・期限予定の登録は未実装です。

`src/Data.php` の `getDatabaseConnection()` をPHPから呼ぶと、`database/alertcast.sqlite` が作成され、`database/migrations/` のSQLファイルのうち未適用のものがファイル名の番号順に適用されます。適用済みのものは `schema_migrations` テーブルに記録されます。PHPの `pdo_sqlite` 拡張が必要です。ページはPHPで動くので、ファイルを直接開かずに、下の「ローカルでの起動」の方法で開いてください。

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
- 機能ごとの実装計画書は `planMD/`（`005_...md` のように次の番号を付ける。`000a`〜`000c` は、Copilot で作っていたころの作業を Git の履歴と会話記録からあとで書き起こしたもの）
- テストは `tests/`
- 画面の動作を担うJavaScriptは `public/assets/js/`
