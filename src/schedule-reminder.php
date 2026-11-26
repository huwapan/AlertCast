<?php
declare(strict_types=1);

//ホームで予定を思い出させるために、予定を取り出す関数をまとめたファイル
//どの関数も「今」の日時を引数で受け取るので、決まった日時を渡してテストできる

//終了日時からこの時間がたっても完了していない予定を「期限を超過した予定」とする（P1D = 1日）
const OVERDUE_GRACE_PERIOD = 'P1D';

//終了日時からこの時間がたっても完了していない予定を「長く放置されている予定」とする（P3D = 3日）
const NEGLECTED_PERIOD = 'P3D';

//DBに保存している日時の形式（YYYY-MM-DD HH:MM:SS）
const STORED_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

//今日の予定を取得する
//開始から終了までの間に今日がかかる時間指定予定を、開始日時の早い順に並べる（完了済みも含める）
function fetchTodaySchedules(PDO $connection, DateTimeImmutable $now): array
{
    //今日の0時と、明日の0時
    $todayStart = $now->setTime(0, 0);
    $tomorrowStart = $todayStart->modify('+1 day');

    //「明日の0時より前に始まり、今日の0時より後に終わる」予定が、今日にかかる予定
    $statement = $connection->prepare(
        "SELECT id, title, start_at, end_at, priority, completed_at
         FROM schedules
         WHERE schedule_type = 'time_block'
           AND start_at < :tomorrow_start
           AND end_at > :today_start
           AND deleted_at IS NULL
         ORDER BY start_at ASC, priority DESC"
    );
    $statement->execute([
        'tomorrow_start' => $tomorrowStart->format(STORED_DATE_TIME_FORMAT),
        'today_start' => $todayStart->format(STORED_DATE_TIME_FORMAT),
    ]);
    return $statement->fetchAll();
}

//完了通知に出す予定を取得する
//終了日時を過ぎてから1日以内で、まだ完了も削除もしていない時間指定予定を、終わった順に並べる
function fetchRecentlyEndedSchedules(PDO $connection, DateTimeImmutable $now): array
{
    $statement = $connection->prepare(
        "SELECT id, title, start_at, end_at
         FROM schedules
         WHERE schedule_type = 'time_block'
           AND end_at <= :now
           AND end_at > :overdue_threshold
           AND completed_at IS NULL
           AND deleted_at IS NULL
         ORDER BY end_at ASC"
    );
    $statement->execute([
        'now' => $now->format(STORED_DATE_TIME_FORMAT),
        'overdue_threshold' => getOverdueThreshold($now)->format(STORED_DATE_TIME_FORMAT),
    ]);
    return $statement->fetchAll();
}

//期限を超過した予定を取得する
//終了日時から1日以上3日未満で、まだ完了も削除もしていない時間指定予定を、終わった順に並べる
//（3日以上たった予定は「長く放置されている予定」に出すので、ここには出さない）
function fetchOverdueSchedules(PDO $connection, DateTimeImmutable $now): array
{
    $statement = $connection->prepare(
        "SELECT id, title, start_at, end_at
         FROM schedules
         WHERE schedule_type = 'time_block'
           AND end_at <= :overdue_threshold
           AND end_at > :neglected_threshold
           AND completed_at IS NULL
           AND deleted_at IS NULL
         ORDER BY end_at ASC"
    );
    $statement->execute([
        'overdue_threshold' => getOverdueThreshold($now)->format(STORED_DATE_TIME_FORMAT),
        'neglected_threshold' => getNeglectedThreshold($now)->format(STORED_DATE_TIME_FORMAT),
    ]);
    return $statement->fetchAll();
}

//長く放置されている予定を取得する
//終了日時から3日以上たっても、まだ完了も削除もしていない時間指定予定を、終わった順に並べる
function fetchNeglectedSchedules(PDO $connection, DateTimeImmutable $now): array
{
    $statement = $connection->prepare(
        "SELECT id, title, start_at, end_at
         FROM schedules
         WHERE schedule_type = 'time_block'
           AND end_at <= :neglected_threshold
           AND completed_at IS NULL
           AND deleted_at IS NULL
         ORDER BY end_at ASC"
    );
    $statement->execute([
        'neglected_threshold' => getNeglectedThreshold($now)->format(STORED_DATE_TIME_FORMAT),
    ]);
    return $statement->fetchAll();
}

//期限超過の境目の日時（今から1日前）を求める
//終了日時がこの日時と同じか、これより前なら期限を超過している
function getOverdueThreshold(DateTimeImmutable $now): DateTimeImmutable
{
    return $now->sub(new DateInterval(OVERDUE_GRACE_PERIOD));
}

//長く放置されている予定の境目の日時（今から3日前）を求める
//終了日時がこの日時と同じか、これより前なら長く放置されている
function getNeglectedThreshold(DateTimeImmutable $now): DateTimeImmutable
{
    return $now->sub(new DateInterval(NEGLECTED_PERIOD));
}

//保存されている日時（例：2026-11-20 11:00:00）から今までに、何日たったかを数える（1日に満たない分は切り捨て）
function countElapsedDays(string $storedValue, DateTimeImmutable $now): int
{
    $dateTime = DateTimeImmutable::createFromFormat(STORED_DATE_TIME_FORMAT, $storedValue, $now->getTimezone());
    if ($dateTime === false || $dateTime > $now) {
        return 0;
    }
    return $dateTime->diff($now)->days;
}
