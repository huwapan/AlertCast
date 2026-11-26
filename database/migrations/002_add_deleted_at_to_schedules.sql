-- 論理削除用の列を追加する
-- NULLなら有効な予定、日時（YYYY-MM-DD HH:MM:SS）が入っていれば削除済みの予定として扱う
ALTER TABLE schedules ADD COLUMN deleted_at TEXT;
