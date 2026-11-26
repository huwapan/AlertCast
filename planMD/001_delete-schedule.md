# 001 予定を削除する（論理削除）

- ストーリー：[docs/storymap.md](../docs/storymap.md) の「削除 > 予定を取り消す > 予定を削除する」
- ブランチ：`feature/delete-schedule`
- 状態：実装済み（ブラウザでの確認と PR がまだ）

## 背景
- ウォーキングスケルトン（各機能の最小限を端から端までつなげたもの）を進めている。
- storymap の記号：`^` は1つ上の段へ、`v` は1つ下の段へ（後回し）。そのため「提案」「改善」はスケルトンに含めない。
- スケルトンの残り：**削除**、変更、想起（今日の予定と期限超過）、確認（完了ボタン）。
- 削除から作る理由：一覧の各行に「ID付きボタン → POST → リダイレクト」の形を作れば、「完了」「変更」でも同じ形を使い回せる。

## 決めたこと
- 削除方式は **論理削除** にする。行は消さず、`deleted_at` 列に削除日時を入れて一覧から隠す。あとで「復元」機能を作れるようにするため。
- 日時は `Asia/Tokyo` の `Y-m-d H:i:s` 形式で保存する（[ProjectMemo](../docs/ProjectMemo.md) に合わせる）。

## ステップ
- [x] 1. ブランチ作成と計画書の保存
- [x] 2. マイグレーション `002_add_deleted_at_to_schedules.sql` を追加（`deleted_at` 列）
- [x] 3. `src/Data.php` のマイグレーション処理を、`database/migrations/*.sql` を番号順にすべて実行する形にする
- [x] 4. `escapeHtml()` と `renderMessagePage()` を `src/view.php` に切り出す
- [x] 5. 削除処理 `public/delete-schedule.php` と `src/schedule-delete.php` を追加
  - POST 以外は 405、`id` が不正なら 422、対象がなければ 404、DBエラーは 500
  - 成功したら `show-table.php?deleted=1` へリダイレクト（303）
- [x] 6. `public/show-table.php` に削除ボタンと確認ダイアログを追加し、削除済みの予定を表示しないようにする
- [x] 7. storymap・README・この計画書を更新

## 確認方法
- [x] 変更した PHP ファイルに `php -l`（文法チェック）を実行する
- [x] 既存のDBに 002 が自動で適用され、`deleted_at` 列が増える
- [x] 予定を登録 → 一覧に削除ボタンが出る → 削除すると一覧から消え、DBには `deleted_at` 付きで残る
- [x] 異常系：GET は 405、`id=abc` は 422、削除済みの ID は 404
- [x] 登録処理がこれまで通り動く
- [ ] ブラウザで確認ダイアログが出る（手動確認）

## メモ
- Windows の curl では、コマンドに直接書いた日本語が Shift_JIS で送られて文字化けする。curl で日本語を送って試すときは、UTF-8 で保存したファイルを `--data-binary @ファイル名` で送る（ブラウザからの送信は UTF-8 なので問題ない）。
