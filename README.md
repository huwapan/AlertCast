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
  Data.php               データ処理の実装予定（現在は空）
docs/                    要件・設計メモ
```

## 現在の状態

画面の静的なひな形です。予定の保存や表示、PHP・データベースとの連携はまだ実装していません。`public/index.html` をブラウザーで開くと確認できます。

## 今後の追加先

- データベースの変更SQLは `database/migrations/`
- テストは `tests/`
- 画面の動作を担うJavaScriptは `public/assets/js/`
