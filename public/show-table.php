<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    $connection = getDatabaseConnection();
    $statement = $connection->query(
        "SELECT title, start_at, end_at, priority
         FROM schedules
         WHERE schedule_type = 'time_block'
         ORDER BY priority DESC, start_at ASC"
    );
    $schedules = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $schedules = null;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>優先度順の予定一覧</title>
        <link rel="stylesheet" href="assets/css/show-table.css">
    </head>
    <body>
        <a href="index.html">ホーム</a>
        <h1>優先度順の予定一覧</h1>
        <?php if ($schedules === null): ?>
            <p role="alert">予定を読み込めませんでした。時間をおいて再度お試しください。</p>
        <?php elseif ($schedules === []): ?>
            <p>登録済みの時間指定予定はありません。</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th scope="col">優先度</th>
                        <th scope="col">タイトル</th>
                        <th scope="col">開始日時</th>
                        <th scope="col">終了日時</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= (int) $schedule['priority'] ?></td>
                            <td><?= escapeHtml($schedule['title']) ?></td>
                            <td><?= escapeHtml($schedule['start_at']) ?></td>
                            <td><?= escapeHtml($schedule['end_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </body>
</html>
