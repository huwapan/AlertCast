<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'schedule-input.php';

header('Content-Type: text/html; charset=UTF-8');

//このページは、URLに id があるかどうかで2つの画面を出し分ける
//・id がないとき：変更する予定を選ぶ一覧
//・id があるとき（例：update-schedule-form.php?id=3）：選んだ予定の今の内容が入った変更フォーム
$requestedId = $_GET['id'] ?? null;
$schedules = [];
$schedule = null;

if ($requestedId === null) {
    //変更できる予定（まだ削除されていない時間指定予定）を開始日時順に取得する（完了済みも含める）
    try {
        $connection = getDatabaseConnection();
        $statement = $connection->query(
            "SELECT id, title, start_at, end_at, priority, completed_at
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
} else {
    //選んだ予定のIDを確認する（1以上の整数のみ）
    if (!is_string($requestedId) || filter_var($requestedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        renderMessagePage('変更できません', '変更する予定の指定が正しくありません。', 422, 'update-schedule-form.php', '変更する予定の一覧に戻る');
        exit;
    }
    //選んだ予定の今の内容を取得する
    try {
        $connection = getDatabaseConnection();
        $statement = $connection->prepare(
            "SELECT id, title, start_at, end_at, priority
             FROM schedules
             WHERE id = :id
               AND schedule_type = 'time_block'
               AND deleted_at IS NULL"
        );
        $statement->execute(['id' => (int) $requestedId]);
        $schedule = $statement->fetch();
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        renderMessagePage('予定を読み込めませんでした', '時間をおいて再度お試しください。', 500, 'update-schedule-form.php', '変更する予定の一覧に戻る');
        exit;
    }
    //予定が見つからなければ、存在しないか削除済み
    if ($schedule === false) {
        renderMessagePage('変更できません', '予定が見つかりませんでした。削除されている可能性があります。', 404, 'update-schedule-form.php', '変更する予定の一覧に戻る');
        exit;
    }
}

//変更処理から戻ってきたときにメッセージを表示する
$isUpdated = ($_GET['updated'] ?? null) === '1';
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>予定の変更</title>
        <link rel="stylesheet" href="assets/css/index.css">
    </head>
    <body>
        <a href="index.php">ホーム</a> | <a href="schedule-edit.html">スケジュール編集ページに戻る</a>
        <h1>予定の変更</h1>
        <?php if ($schedule === null): ?>
            <!--変更する予定を選ぶ一覧-->
            <?php if ($isUpdated): ?>
                <p role="status">予定を変更しました。</p>
            <?php endif; ?>
            <?php if ($schedules === null): ?>
                <p role="alert">予定を読み込めませんでした。時間をおいて再度お試しください。</p>
            <?php elseif ($schedules === []): ?>
                <p>変更できる時間指定予定はありません。</p>
            <?php else: ?>
                <!--変更する予定を1つ選んで、変更フォームを開く（id をURLに付けて同じページを開く）-->
                <form action="update-schedule-form.php" method="get">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">選択</th>
                                <th scope="col">タイトル</th>
                                <th scope="col">開始日時</th>
                                <th scope="col">終了日時</th>
                                <th scope="col">優先度</th>
                                <th scope="col">状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $listedSchedule): ?>
                                <?php $radioId = 'schedule-' . (int) $listedSchedule['id']; ?>
                                <tr>
                                    <td>
                                        <input type="radio" id="<?= $radioId ?>" name="id" value="<?= (int) $listedSchedule['id'] ?>" required>
                                    </td>
                                    <td><label for="<?= $radioId ?>"><?= escapeHtml($listedSchedule['title']) ?></label></td>
                                    <td><?= escapeHtml($listedSchedule['start_at']) ?></td>
                                    <td><?= escapeHtml($listedSchedule['end_at']) ?></td>
                                    <td><?= (int) $listedSchedule['priority'] ?></td>
                                    <!--completed_at に日時が入っていれば完了-->
                                    <td><?= $listedSchedule['completed_at'] === null ? '未完了' : '完了' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <button type="submit">選択した予定を変更</button>
                    </p>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <!--選んだ予定の変更フォーム（今の内容を入れておく）-->
            <section>
                <h2>「<?= escapeHtml($schedule['title']) ?>」を変更する</h2>
                <form action="update-schedule.php" method="post">
                    <input type="hidden" name="id" value="<?= (int) $schedule['id'] ?>">
                    <p>
                        <label for="title">タイトル</label>
                        <input id="title" name="title" type="text" value="<?= escapeHtml($schedule['title']) ?>" required>
                    </p>
                    <p>
                        <label for="start_at">開始日時</label>
                        <input id="start_at" name="start_at" type="datetime-local" value="<?= escapeHtml(formatForDateTimeInput($schedule['start_at'])) ?>" required>
                    </p>
                    <p>
                        <label for="end_at">終了日時</label>
                        <input id="end_at" name="end_at" type="datetime-local" value="<?= escapeHtml(formatForDateTimeInput($schedule['end_at'])) ?>" required>
                    </p>
                    <p>
                        <label for="priority">優先度（1以上）</label>
                        <input id="priority" name="priority" type="number" min="1" step="1" value="<?= (int) $schedule['priority'] ?>" required>
                    </p>
                    <button type="submit">変更を保存</button>
                </form>
                <p><a href="update-schedule-form.php">変更する予定の一覧に戻る</a></p>
            </section>
        <?php endif; ?>
    </body>
</html>
