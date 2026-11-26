<?php
declare(strict_types=1);

//時間指定予定のフォームの入力をチェックする関数をまとめたファイル
//予定の登録（schedule-create.php）と変更（schedule-update.php）の両方で使う

//フォームの日時（例：2026-11-26T14:40）を、保存用の形（例：2026-11-26 14:40:00）に変換する
//形が正しくない日時や、存在しない日時（2月30日など）のときは null を返す
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

//保存されている日時（例：2026-11-26 14:40:00）を、フォームの入力欄に入れる形（例：2026-11-26T14:40）に戻す
//入力欄は分までなので秒は落とす。形が正しくないときは空文字を返す（入力欄が空になる）
function formatForDateTimeInput(string $storedValue): string
{
    $dateTime = DateTimeImmutable::createFromFormat(
        'Y-m-d H:i:s',
        $storedValue,
        new DateTimeZone('Asia/Tokyo')
    );
    if ($dateTime === false) {
        return '';
    }
    return $dateTime->format('Y-m-d\TH:i');
}

//時間指定予定の入力（タイトル・開始日時・終了日時・優先度）をチェックする
//正しければ保存する値の配列を、正しくなければエラーメッセージの文字列を返す
function validateTimeBlockInput(array $input): array|string
{
    $title = $input['title'] ?? null;
    $startAt = $input['start_at'] ?? null;
    $endAt = $input['end_at'] ?? null;
    $priority = $input['priority'] ?? null;

    if (!is_string($title) || trim($title) === '') {
        return 'タイトルを入力してください。';
    }

    if (!is_string($startAt) || !is_string($endAt)) {
        return '開始日時と終了日時を入力してください。';
    }

    $normalizedStartAt = parseLocalDateTime($startAt);
    $normalizedEndAt = parseLocalDateTime($endAt);
    if ($normalizedStartAt === null || $normalizedEndAt === null) {
        return '開始日時と終了日時を正しく入力してください。';
    }

    if ($normalizedStartAt >= $normalizedEndAt) {
        return '終了日時は開始日時より後にしてください。';
    }

    if (!is_string($priority) || filter_var($priority, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        return '優先度は1以上の整数で入力してください。';
    }

    return [
        'title' => trim($title),
        'start_at' => $normalizedStartAt,
        'end_at' => $normalizedEndAt,
        'priority' => (int) $priority,
    ];
}
