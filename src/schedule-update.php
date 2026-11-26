<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'schedule-input.php';

//エラー画面の戻り先（変更する予定を選ぶ一覧）
const UPDATE_LIST_URL = 'update-schedule-form.php';
const UPDATE_LIST_LABEL = '変更する予定の一覧に戻る';

//POST以外のアクセスは受け付けない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderMessagePage('変更できません', 'このページは予定の変更フォームから送信してください。', 405, UPDATE_LIST_URL, UPDATE_LIST_LABEL);
    exit;
}

//変更する予定のIDを確認する（1以上の整数のみ）
$id = $_POST['id'] ?? null;
if (!is_string($id) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    renderMessagePage('変更できません', '変更する予定の指定が正しくありません。', 422, UPDATE_LIST_URL, UPDATE_LIST_LABEL);
    exit;
}

//入力をチェックする（正しくなければ、同じ予定の変更フォームに戻れるようにする）
$schedule = validateTimeBlockInput($_POST);
if (is_string($schedule)) {
    renderMessagePage('入力内容を確認してください', $schedule, 422, 'update-schedule-form.php?id=' . (int) $id, '変更フォームに戻る');
    exit;
}

//変更日時（日本時間）
$now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d H:i:s');

try {
    $connection = getDatabaseConnection();
    //削除されていない時間指定予定だけを変更する（完了の記録 completed_at はそのまま残す）
    $statement = $connection->prepare(
        "UPDATE schedules
         SET title = :title, start_at = :start_at, end_at = :end_at, priority = :priority, updated_at = :updated_at
         WHERE id = :id AND schedule_type = 'time_block' AND deleted_at IS NULL"
    );
    $statement->execute([
        'title' => $schedule['title'],
        'start_at' => $schedule['start_at'],
        'end_at' => $schedule['end_at'],
        'priority' => $schedule['priority'],
        'updated_at' => $now,
        'id' => (int) $id,
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    renderMessagePage('変更に失敗しました', 'データベースの更新中にエラーが発生しました。', 500, UPDATE_LIST_URL, UPDATE_LIST_LABEL);
    exit;
}

//更新された行がなければ、予定が存在しないか削除済み
if ($statement->rowCount() === 0) {
    renderMessagePage('変更できません', '予定が見つかりませんでした。削除されている可能性があります。', 404, UPDATE_LIST_URL, UPDATE_LIST_LABEL);
    exit;
}

//変更できたら一覧に戻る（再読み込みで二重送信しないように303でリダイレクト）
header('Location: update-schedule-form.php?updated=1', true, 303);
exit;
