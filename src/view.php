<?php
declare(strict_types=1);

//HTMLに出力する文字列をエスケープする（<や&などをそのまま表示できる形に変換する）
function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

//エラーなどのメッセージだけを表示する簡単なページを出力する
function renderMessagePage(
    string $heading,
    string $message,
    int $statusCode,
    string $backUrl = 'schedule-edit.html',
    string $backLabel = '登録画面に戻る'
): void {
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');

    $escapedHeading = escapeHtml($heading);
    echo '<!DOCTYPE html><html lang="ja"><meta charset="UTF-8"><title>'
        . $escapedHeading
        . '</title><body><h1>'
        . $escapedHeading
        . '</h1><p>'
        . escapeHtml($message)
        . '</p><p><a href="'
        . escapeHtml($backUrl)
        . '">'
        . escapeHtml($backLabel)
        . '</a> | '
        . '<a href="index.php">ホーム</a></p></body></html>';
}
