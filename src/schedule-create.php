<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Data.php';

function renderMessagePage(string $heading, string $message, int $statusCode): void
{
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');

    $escapedHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $escapedMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="ja"><meta charset="UTF-8"><title>'
        . $escapedHeading
        . '</title><body><h1>'
        . $escapedHeading
        . '</h1><p>'
        . $escapedMessage
        . '</p><p><a href="schedule-edit.html">登録画面に戻る</a> | '
        . '<a href="index.html">ホーム</a></p></body></html>';
}

function parseLocalDateTime(string $value): ?string
{
    $dateTime = DateTimeImmutable::createFromFormat(
        '!Y-m-d\TH:i',
        $value,
        new DateTimeZone('Asia/Tokyo')
    );
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $dateTime === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $dateTime->format('Y-m-d\TH:i') !== $value
    ) {
        return null;
    }

    return $dateTime->format('Y-m-d H:i:s');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderMessagePage('登録できません', 'このページは予定登録フォームから送信してください。', 405);
    exit;
}

$title = $_POST['title'] ?? null;
$startAt = $_POST['start_at'] ?? null;
$endAt = $_POST['end_at'] ?? null;
$priority = $_POST['priority'] ?? null;

if (!is_string($title) || trim($title) === '') {
    renderMessagePage('入力内容を確認してください', 'タイトルを入力してください。', 422);
    exit;
}

if (!is_string($startAt) || !is_string($endAt)) {
    renderMessagePage('入力内容を確認してください', '開始日時と終了日時を入力してください。', 422);
    exit;
}

$normalizedStartAt = parseLocalDateTime($startAt);
$normalizedEndAt = parseLocalDateTime($endAt);
if ($normalizedStartAt === null || $normalizedEndAt === null) {
    renderMessagePage('入力内容を確認してください', '開始日時と終了日時を正しく入力してください。', 422);
    exit;
}

if ($normalizedStartAt >= $normalizedEndAt) {
    renderMessagePage('入力内容を確認してください', '終了日時は開始日時より後にしてください。', 422);
    exit;
}

if (!is_string($priority) || filter_var($priority, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    renderMessagePage('入力内容を確認してください', '優先度は1以上の整数で入力してください。', 422);
    exit;
}

try {
    $connection = getDatabaseConnection();
    $statement = $connection->prepare(
        'INSERT INTO schedules (title, schedule_type, start_at, end_at, priority)
         VALUES (:title, :schedule_type, :start_at, :end_at, :priority)'
    );
    $statement->execute([
        'title' => trim($title),
        'schedule_type' => 'time_block',
        'start_at' => $normalizedStartAt,
        'end_at' => $normalizedEndAt,
        'priority' => (int) $priority,
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    renderMessagePage('登録に失敗しました', 'データベースへの保存中にエラーが発生しました。', 500);
    exit;
}

renderMessagePage('登録しました', '時間指定予定を登録しました。', 201);
