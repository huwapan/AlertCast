-- 作成日時（created_at）を、世界標準時（UTC）から日本時間に直す
-- これまで created_at は、列の初期値（CURRENT_TIMESTAMP = UTC）で入っていたので、日本時間より9時間早かった
-- これからは、予定を登録するときにプログラムが日本時間を入れる

-- 一度も更新していない予定は、updated_at も初期値の UTC のままで、created_at と同じ値になっている
-- そういう予定だけ updated_at も9時間進める（created_at を直す前に行う）
-- 完了・削除・変更をした予定の updated_at は、プログラムが日本時間で入れているので直さない
UPDATE schedules
SET updated_at = datetime(updated_at, '+9 hours')
WHERE updated_at = created_at;

-- すべての予定の created_at を9時間進める
UPDATE schedules
SET created_at = datetime(created_at, '+9 hours');
