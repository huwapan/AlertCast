<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';

header('Content-Type: text/html; charset=UTF-8');

//完了の通知に出す予定を取得する
//終了日時を過ぎていて、まだ完了も削除もしていない時間指定予定を、終わった順に並べる
$now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d H:i:s');
try {
    $connection = getDatabaseConnection();
    $statement = $connection->prepare(
        "SELECT id, title, start_at, end_at
         FROM schedules
         WHERE schedule_type = 'time_block'
           AND end_at <= :now
           AND completed_at IS NULL
           AND deleted_at IS NULL
         ORDER BY end_at ASC"
    );
    $statement->execute(['now' => $now]);
    $endedSchedules = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $endedSchedules = null;
}

//完了処理から戻ってきたときにメッセージを表示する
$isCompleted = ($_GET['completed'] ?? null) === '1';
?>
<!--
- 想起させる機能
    - 朝に一日の予定一覧をアラーム表示
    - 期限を超過した予定を表示
    - 長期間放置されている予定を表示
        - 優先度の高い予定の期日前警告
        - 今週の完了済み予定一覧
- スケジュール管理機能
    - スケジュールの入力・変更・破棄機能
    - スケジュール完了の登録機能
        - 完了済み予定一覧（日時・優先度で絞れる）
        - 予定に優先度をつける機能
- 提案する機能
    - 設定されたカテゴリに関連するイベント一覧
    - 入力された予定から一日のスケジュール案を生成
        - 以前のデータからスケジュール案を生成
        - 生成したスケジュール案のフィードバックを受けて改善する
-->
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>お知らせ放送</title>
        <link rel="stylesheet" href="assets/css/index.css">
    </head>
    <body>
        <h1>お知らせ放送</h1>
        <?php if ($isCompleted): ?>
            <p role="status">予定を完了にしました。</p>
        <?php endif; ?>
        <!--終わった予定の完了確認の通知（終わった予定があるときだけ表示する）-->
        <?php if ($endedSchedules === null): ?>
            <p role="alert">終わった予定を読み込めませんでした。時間をおいて再度お試しください。</p>
        <?php elseif ($endedSchedules !== []): ?>
            <section aria-labelledby="completion-notice-heading">
                <h2 id="completion-notice-heading">終わった予定があります。完了しましたか？</h2>
                <ul>
                    <?php foreach ($endedSchedules as $schedule): ?>
                        <li>
                            <?= escapeHtml($schedule['title']) ?>
                            （<?= escapeHtml($schedule['start_at']) ?> 〜 <?= escapeHtml($schedule['end_at']) ?>）
                            <form action="complete-schedule.php" method="post">
                                <input type="hidden" name="id" value="<?= (int) $schedule['id'] ?>">
                                <button type="submit">完了</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
        <!--今週のスケジュール一覧-->
        <p>今週のスケジュール一覧</p>
        <!--予定の入力・変更・破棄・復元が出来るページに飛ぶボタン-->
        <a href="schedule-edit.html">スケジュール編集ページ</a>
        <!--期限が近い予定一覧-->
        <p>期限が近い予定一覧</p>
        <!--期限を超過した予定一覧-->
        <p>期限を超過した予定一覧</p>
        <!--データ閲覧ページに飛ぶボタン
        完了済み予定一覧
        長期間放置されている予定一覧
        -->
        <a href="show-table.php">優先度順の予定一覧</a>
    </body>
</html>