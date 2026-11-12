// スケジュールテーブルの作成
CREATE TABLE schedules (
    // スケジュールのID
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    // スケジュールのタイトル
    title TEXT NOT NULL CHECK (length(trim(title)) > 0),
    // スケジュールの種類（time_block, all_day, deadlineのいずれか）
    schedule_type TEXT NOT NULL CHECK (
        // schedule_typeがtime_block, all_day, deadlineのいずれかであることを確認する制約条件
        schedule_type IN ('time_block', 'all_day', 'deadline')
    ),
    // スケジュールの時刻（time_blockの場合に使用）
    start_at TEXT,    
    // スケジュールの終了時刻（time_blockの場合に使用）
    end_at TEXT,
    // スケジュールの日時（all_dayの場合に使用）
    scheduled_date TEXT,
    // スケジュールの期間（deadlineの場合に使用）
    period_start_date TEXT,
    // スケジュールの期間終了日（deadlineの場合に使用）
    period_end_date TEXT,
    // スケジュールの優先度（time_blockの場合に使用）
    priority INTEGER,
    // スケジュールの完了日時
    completed_at TEXT,
    // スケジュールの作成日時と更新日時
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    // スケジュールの制約条件
    // priorityがNULLでない場合、1以上であることを確認する制約条件
    CHECK (priority IS NULL OR priority >= 1),
    CHECK (
        (
            // time_blockの場合の制約条件
            schedule_type = 'time_block'
            AND start_at IS NOT NULL
            AND end_at IS NOT NULL
            AND start_at < end_at
            AND scheduled_date IS NULL
            AND period_start_date IS NULL
            AND period_end_date IS NULL
            AND priority IS NOT NULL
        )
        OR
        (
            // all_dayの場合の制約条件
            schedule_type = 'all_day'
            AND scheduled_date IS NOT NULL
            AND start_at IS NULL
            AND end_at IS NULL
            AND period_start_date IS NULL
            AND period_end_date IS NULL
        )
        OR
        (
            // deadlineの場合の制約条件
            schedule_type = 'deadline'
            AND period_start_date IS NOT NULL
            AND period_end_date IS NOT NULL
            AND period_start_date <= period_end_date
            AND start_at IS NULL
            AND end_at IS NULL
            AND scheduled_date IS NULL
        )
    )
);
// time_blockの開始時刻用インデックス
CREATE INDEX idx_schedules_start_at
    ON schedules (start_at)
    WHERE schedule_type = 'time_block';
// all_dayの日付用インデックス
CREATE INDEX idx_schedules_scheduled_date
    ON schedules (scheduled_date)
    WHERE schedule_type = 'all_day';
// deadlineの期間終了日用インデックス
CREATE INDEX idx_schedules_period_end_date
    ON schedules (period_end_date)
    WHERE schedule_type = 'deadline';
