<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'schedule-input.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderMessagePage('登録できません', 'このページは予定登録フォームから送信してください。', 405);
    exit;
}

//入力をチェックする（正しくなければエラーメッセージが返ってくる）
$schedule = validateTimeBlockInput($_POST);
if (is_string($schedule)) {
    renderMessagePage('入力内容を確認してください', $schedule, 422);
    exit;
}

try {
    $connection = getDatabaseConnection();
    $statement = $connection->prepare(
        'INSERT INTO schedules (title, schedule_type, start_at, end_at, priority)
         VALUES (:title, :schedule_type, :start_at, :end_at, :priority)'
    );
    $statement->execute([
        'title' => $schedule['title'],
        'schedule_type' => 'time_block',
        'start_at' => $schedule['start_at'],
        'end_at' => $schedule['end_at'],
        'priority' => $schedule['priority'],
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    renderMessagePage('登録に失敗しました', 'データベースへの保存中にエラーが発生しました。', 500);
    exit;
}

header('Location: schedule-edit.html?registered=1', true, 303);
exit;
