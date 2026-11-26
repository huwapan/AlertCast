<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';

header('Content-Type: text/html; charset=UTF-8');

//削除できる予定（まだ削除されていない時間指定予定）を開始日時順に取得する
//終日予定・期限予定は登録機能ができたら対象に加える
try {
    $connection = getDatabaseConnection();
    $statement = $connection->query(
        "SELECT id, title, start_at, end_at, priority
         FROM schedules
         WHERE schedule_type = 'time_block'
           AND deleted_at IS NULL
         ORDER BY start_at ASC"
    );
    $schedules = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $schedules = null;
}

//削除処理から戻ってきたときにメッセージを表示する
$isDeleted = ($_GET['deleted'] ?? null) === '1';
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>予定の削除</title>
        <link rel="stylesheet" href="assets/css/index.css">
    </head>
    <body>
        <a href="index.html">ホーム</a> | <a href="schedule-edit.html">スケジュール編集ページに戻る</a>
        <h1>予定の削除</h1>
        <?php if ($isDeleted): ?>
            <p role="status">予定を削除しました。</p>
        <?php endif; ?>
        <?php if ($schedules === null): ?>
            <p role="alert">予定を読み込めませんでした。時間をおいて再度お試しください。</p>
        <?php elseif ($schedules === []): ?>
            <p>削除できる時間指定予定はありません。</p>
        <?php else: ?>
            <!--削除する予定を1つ選んで送信する（送信前に確認ダイアログを出す）-->
            <form action="delete-schedule.php" method="post" onsubmit="return confirm('選択した予定を削除しますか？');">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">選択</th>
                            <th scope="col">タイトル</th>
                            <th scope="col">開始日時</th>
                            <th scope="col">終了日時</th>
                            <th scope="col">優先度</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <?php $radioId = 'schedule-' . (int) $schedule['id']; ?>
                            <tr>
                                <td>
                                    <input type="radio" id="<?= $radioId ?>" name="id" value="<?= (int) $schedule['id'] ?>" required>
                                </td>
                                <td><label for="<?= $radioId ?>"><?= escapeHtml($schedule['title']) ?></label></td>
                                <td><?= escapeHtml($schedule['start_at']) ?></td>
                                <td><?= escapeHtml($schedule['end_at']) ?></td>
                                <td><?= (int) $schedule['priority'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button type="submit">選択した予定を削除</button>
                </p>
            </form>
        <?php endif; ?>
    </body>
</html>
