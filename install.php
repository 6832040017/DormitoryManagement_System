<?php require 'lib.php';
// ตั้งค่าฐานข้อมูลใน lib.php ก่อน แล้วสร้างฐานข้อมูลว่างชื่อ dorm (utf8mb4) แล้วเปิดไฟล์นี้ 1 ครั้ง
if(db()->query("SHOW TABLES LIKE 'users'")->fetch()&&q('SELECT COUNT(*) FROM users')->fetchColumn())exit('ติดตั้งแล้ว กรุณาลบไฟล์ install.php');
foreach(array_filter(array_map('trim',explode(";\n",file_get_contents(__DIR__.'/database/schema.sql')))) as $s)db()->exec($s);
$ins=fn($r,$n,$u,$p)=>q('INSERT INTO users(role,name,username,password,must_change)VALUES(?,?,?,?,1)',[$r,$n,$u,password_hash($p,PASSWORD_DEFAULT)]);
$ins('admin','ผู้ดูแลระบบ','admin','admin123');$ins('owner','เจ้าของหอ','owner','owner123');$ins('tenant','สมชาย ใจดี','tenant','tenant123');
foreach([['101','แอร์',3500],['102','แอร์',3500],['201','พัดลม',2500],['202','พัดลม',2500]] as $r)q('INSERT INTO rooms(room_no,type,rent,descr)VALUES(?,?,?,?)',[...$r,'ห้องสะอาด มีเฟอร์นิเจอร์']);
q("UPDATE rooms SET tenant_id=3,status='occupied' WHERE room_no='101'");q("INSERT INTO leases(room_id,tenant_id,start_date,end_date,rent,deposit)SELECT id,3,CURDATE()-INTERVAL 10 MONTH,CURDATE()+INTERVAL 50 DAY,rent,1000 FROM rooms WHERE room_no='101'");
echo 'ติดตั้งสำเร็จ! <a href="login.php">เข้าสู่ระบบ</a><br>admin/admin123 · owner/owner123 · tenant/tenant123<br><b>กรุณาลบ install.php และเปลี่ยนรหัสผ่านทันที</b>';
