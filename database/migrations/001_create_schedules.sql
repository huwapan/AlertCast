// スケジュールテーブルの作成

CREATE TABLE schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL CHECK (length(trim(title)) > 0),
    schedule_type TEXT NOT NULL CHECK (
        schedule_type IN ('time_block', 'all_day', 'deadline')
    ),
    start_at TEXT,
    end_at TEXT,
    scheduled_date TEXT,
    period_start_date TEXT,
    period_end_date TEXT,
    priority INTEGER,
    completed_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (priority IS NULL OR priority >= 1),
    CHECK (
        (
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
            schedule_type = 'all_day'
            AND scheduled_date IS NOT NULL
            AND start_at IS NULL
            AND end_at IS NULL
            AND period_start_date IS NULL
            AND period_end_date IS NULL
        )
        OR
        (
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

CREATE INDEX idx_schedules_start_at
    ON schedules (start_at)
    WHERE schedule_type = 'time_block';

CREATE INDEX idx_schedules_scheduled_date
    ON schedules (scheduled_date)
    WHERE schedule_type = 'all_day';

CREATE INDEX idx_schedules_period_end_date
    ON schedules (period_end_date)
    WHERE schedule_type = 'deadline';
