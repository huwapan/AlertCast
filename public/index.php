<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'schedule-reminder.php';

header('Content-Type: text/html; charset=UTF-8');

//ホームに出す予定を取得する（今の日時は日本時間）
//・今日の予定：今日にかかる予定（完了済みも含める）
//・完了通知：終わってから1日以内で、まだ完了していない予定
//・期限を超過した予定：終わってから1日以上たっても、まだ完了していない予定
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo'));
$todaySchedules = [];
$endedSchedules = [];
$overdueSchedules = [];
$hasLoadError = false;
try {
    $connection = getDatabaseConnection();
    $todaySchedules = fetchTodaySchedules($connection, $now);
    $endedSchedules = fetchRecentlyEndedSchedules($connection, $now);
    $overdueSchedules = fetchOverdueSchedules($connection, $now);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $hasLoadError = true;
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
        <?php if ($hasLoadError): ?>
            <p role="alert">予定を読み込めませんでした。時間をおいて再度お試しください。</p>
        <?php else: ?>
            <!--今日の予定一覧（今日にかかる予定を開始日時の早い順に表示する）-->
            <section aria-labelledby="today-schedules-heading">
                <h2 id="today-schedules-heading">今日の予定</h2>
                <?php if ($todaySchedules === []): ?>
                    <p>今日の予定はありません。</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">開始日時</th>
                                <th scope="col">終了日時</th>
                                <th scope="col">タイトル</th>
                                <th scope="col">優先度</th>
                                <th scope="col">状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todaySchedules as $schedule): ?>
                                <tr>
                                    <td><?= escapeHtml($schedule['start_at']) ?></td>
                                    <td><?= escapeHtml($schedule['end_at']) ?></td>
                                    <td><?= escapeHtml($schedule['title']) ?></td>
                                    <td><?= (int) $schedule['priority'] ?></td>
                                    <!--completed_at に日時が入っていれば完了-->
                                    <td><?= $schedule['completed_at'] === null ? '未完了' : '完了' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
            <!--終わった予定の完了確認の通知（終わってから1日以内の予定があるときだけ表示する）-->
            <?php if ($endedSchedules !== []): ?>
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
            <!--期限を超過した予定一覧（終わってから1日たっても完了していない予定があるときだけ表示する）-->
            <?php if ($overdueSchedules !== []): ?>
                <section aria-labelledby="overdue-schedules-heading">
                    <h2 id="overdue-schedules-heading">期限を超過した予定があります。忘れていませんか？</h2>
                    <ul>
                        <?php foreach ($overdueSchedules as $schedule): ?>
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
        <?php endif; ?>
        <!--今週のスケジュール一覧-->
        <p>今週のスケジュール一覧</p>
        <!--予定の入力・変更・破棄・復元が出来るページに飛ぶボタン-->
        <a href="schedule-edit.html">スケジュール編集ページ</a>
        <!--期限が近い予定一覧-->
        <p>期限が近い予定一覧</p>
        <!--データ閲覧ページに飛ぶボタン
        完了済み予定一覧
        長期間放置されている予定一覧
        -->
        <a href="show-table.php">優先度順の予定一覧</a>
    </body>
</html>