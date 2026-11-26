<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';

//エラー画面の戻り先（削除フォーム）
const DELETE_BACK_URL = 'delete-schedule-form.php';
const DELETE_BACK_LABEL = '削除フォームに戻る';

//POST以外のアクセスは受け付けない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderMessagePage('削除できません', 'このページは予定の削除フォームから送信してください。', 405, DELETE_BACK_URL, DELETE_BACK_LABEL);
    exit;
}

//削除する予定のIDを確認する（1以上の整数のみ）
$id = $_POST['id'] ?? null;
if (!is_string($id) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    renderMessagePage('削除できません', '削除する予定の指定が正しくありません。', 422, DELETE_BACK_URL, DELETE_BACK_LABEL);
    exit;
}

//削除日時（日本時間）
$now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d H:i:s');

try {
    $connection = getDatabaseConnection();
    //論理削除：行は消さずに deleted_at に削除日時を入れる（まだ削除されていない予定だけが対象）
    $statement = $connection->prepare(
        'UPDATE schedules
         SET deleted_at = :deleted_at, updated_at = :updated_at
         WHERE id = :id AND deleted_at IS NULL'
    );
    $statement->execute([
        'deleted_at' => $now,
        'updated_at' => $now,
        'id' => (int) $id,
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    renderMessagePage('削除に失敗しました', 'データベースの更新中にエラーが発生しました。', 500, DELETE_BACK_URL, DELETE_BACK_LABEL);
    exit;
}

//更新された行がなければ、予定が存在しないか削除済み
if ($statement->rowCount() === 0) {
    renderMessagePage('削除できません', '予定が見つかりませんでした。すでに削除されている可能性があります。', 404, DELETE_BACK_URL, DELETE_BACK_LABEL);
    exit;
}

//削除できたら削除フォームに戻る（再読み込みで二重送信しないように303でリダイレクト）
header('Location: delete-schedule-form.php?deleted=1', true, 303);
exit;
