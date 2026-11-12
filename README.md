# お知らせ放送

個人向けのスケジュール管理アプリです。
機能要件は [docs/ProjectMemo.md](docs/ProjectMemo.md) を参照してください。

## ディレクトリ構成
```text
public/                  ブラウザーに公開するページとアセット
  index.html
  schedule-edit.html
  show-table.html
  assets/
    css/
src/                     PHPのアプリケーション処理
  Data.php               SQLite接続と初回マイグレーション
docs/                    要件・設計メモ
```

## 現在の状態

画面は静的なひな形で、予定の登録・表示などのCRUD機能は未実装です。

`src/Data.php` の `getDatabaseConnection()` をPHPから呼ぶと、`database/alertcast.sqlite` が作成され、`database/migrations/` の初回マイグレーションが適用されます。PHPの `pdo_sqlite` 拡張が必要です。`public/index.html` を直接開いただけではデータベースは作成されません。

## 今後の追加先

- データベースの変更SQLは `database/migrations/`
- テストは `tests/`
- 画面の動作を担うJavaScriptは `public/assets/js/`
