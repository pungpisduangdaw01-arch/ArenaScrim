-- เพิ่มข้อมูลส่วนตัวของผู้เล่นแต่ละคนในทีม (รันครั้งเดียว สำหรับฐานข้อมูลที่ติดตั้งไว้ก่อนแล้ว)
ALTER TABLE team_members
    ADD COLUMN nickname     VARCHAR(100) NULL COMMENT 'ชื่อเล่น' AFTER main_role,
    ADD COLUMN fullname     VARCHAR(255) NULL COMMENT 'ชื่อ-นามสกุลจริง' AFTER nickname,
    ADD COLUMN open_id      VARCHAR(100) NULL COMMENT 'OpenID ในเกม' AFTER fullname,
    ADD COLUMN full_id      VARCHAR(100) NULL COMMENT 'Full ID ตัวละคร' AFTER open_id,
    ADD COLUMN current_rank VARCHAR(50)  NULL COMMENT 'แรงก์ปัจจุบัน' AFTER full_id,
    ADD COLUMN contact      VARCHAR(255) NULL COMMENT 'ช่องทางติดต่อ เช่น Line / Discord' AFTER current_rank,
    ADD COLUMN member_note  TEXT         NULL COMMENT 'หมายเหตุ ฮีโร่ถนัด ฯลฯ' AFTER contact,
    ADD COLUMN info_updated_at DATETIME  NULL COMMENT 'แก้ไขข้อมูลส่วนตัวล่าสุด' AFTER member_note;
