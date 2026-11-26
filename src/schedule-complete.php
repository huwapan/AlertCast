<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';

//エラー画面の戻り先（ホーム）
const COMPLETE_BACK_URL = 'index.php';
const COMPLETE_BACK_LABEL = 'ホームに戻る';

//POST以外のアクセスは受け付けない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderMessagePage('完了にできません', 'このページはホームの完了ボタンから送信してください。', 405, COMPLETE_BACK_URL, COMPLETE_BACK_LABEL);
    exit;
}

//完了にする予定のIDを確認する（1以上の整数のみ）
$id = $_POST['id'] ?? null;
if (!is_string($id) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    renderMessagePage('完了にできません', '完了にする予定の指定が正しくありません。', 422, COMPLETE_BACK_URL, COMPLETE_BACK_LABEL);
    exit;
}

//完了日時（日本時間）
$now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d H:i:s');

try {
    $connection = getDatabaseConnection();
    //completed_at に完了日時を入れる（まだ完了しておらず、削除もされていない予定だけが対象）
    $statement = $connection->prepare(
        'UPDATE schedules
         SET completed_at = :completed_at, updated_at = :updated_at
         WHERE id = :id AND completed_at IS NULL AND deleted_at IS NULL'
    );
    $statement->execute([
        'completed_at' => $now,
        'updated_at' => $now,
        'id' => (int) $id,
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    renderMessagePage('完了にできませんでした', 'データベースの更新中にエラーが発生しました。', 500, COMPLETE_BACK_URL, COMPLETE_BACK_LABEL);
    exit;
}

//更新された行がなければ、予定が存在しないか、完了済み・削除済み
if ($statement->rowCount() === 0) {
    renderMessagePage('完了にできません', '予定が見つかりませんでした。すでに完了しているか、削除されている可能性があります。', 404, COMPLETE_BACK_URL, COMPLETE_BACK_LABEL);
    exit;
}

//完了にできたらホームに戻る（再読み込みで二重送信しないように303でリダイレクト）
header('Location: index.php?completed=1', true, 303);
exit;
