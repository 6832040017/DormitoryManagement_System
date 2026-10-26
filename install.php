<?php
// ตัวติดตั้งระบบ (ใช้ครั้งเดียว) — เสร็จแล้วต้องลบไฟล์นี้ออกจากเซิร์ฟเวอร์
require 'lib.php';
function page($body){echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ติดตั้งระบบหอพัก</title><link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/style.css"></head><body><header><span class="logo">🏠 ติดตั้งระบบบริการจัดการหอพัก</span></header><main><div class="card login" style="max-width:560px">'.$body.'</div></main></body></html>';exit;}
try{db()->query('SELECT 1');}catch(Throwable $x){page('<h2>เชื่อมต่อฐานข้อมูลไม่ได้</h2><p>กรุณาแก้บรรทัดที่ 3 ในไฟล์ <b>lib.php</b> ให้ตรงกับฐานข้อมูลของคุณ (DB_HOST, DB_NAME, DB_USER, DB_PASS) แล้วรีเฟรชหน้านี้</p><p><small>รหัสข้อผิดพลาด: '.e($x->getCode()).'</small></p>');}
$done=false;try{$done=(int)q('SELECT COUNT(*) FROM users')->fetchColumn()>0;}catch(Throwable $x){}
if($done)page('<h2>ติดตั้งเรียบร้อยแล้ว</h2><p>ระบบมีผู้ใช้อยู่แล้ว กรุณา <b>ลบไฟล์ install.php</b> ออกจากเซิร์ฟเวอร์ แล้ว <a href="login.php">เข้าสู่ระบบ</a></p>');
$err='';$v=['dorm_name'=>'','owner_name'=>'','admin_user'=>'admin','owner_user'=>'owner','demo'=>false];
if($_SERVER['REQUEST_METHOD']==='POST'){chk();
 foreach(['dorm_name','owner_name','admin_user','owner_user']as$k)$v[$k]=trim($_POST[$k]??'');$v['demo']=!empty($_POST['demo']);
 $ap=$_POST['admin_pass']??'';$op=$_POST['owner_pass']??'';
 if($v['dorm_name']===''||$v['owner_name']==='')$err='กรุณากรอกชื่อหอพักและชื่อเจ้าของหอ';
 elseif(!preg_match('/^\w{3,30}$/',$v['admin_user'])||!preg_match('/^\w{3,30}$/',$v['owner_user']))$err='ชื่อผู้ใช้ต้องเป็นตัวอักษรอังกฤษ/ตัวเลข 3-30 ตัว';
 elseif($v['admin_user']===$v['owner_user']||($v['demo']&&in_array('tenant',[$v['admin_user'],$v['owner_user']],true)))$err='ชื่อผู้ใช้ซ้ำกัน กรุณาตั้งให้ต่างกัน';
 elseif(strlen($ap)<8||strlen($op)<8)$err='รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
 elseif($ap!==($_POST['admin_pass2']??'')||$op!==($_POST['owner_pass2']??''))$err='รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
 else{try{
  foreach(array_filter(array_map('trim',explode(";\n",file_get_contents(__DIR__.'/database/schema.sql'))))as$s)db()->exec($s);
  $ins=fn($r,$n,$u,$p,$mc)=>q('INSERT INTO users(role,name,username,password,must_change)VALUES(?,?,?,?,?)',[$r,$n,$u,password_hash($p,PASSWORD_DEFAULT),$mc]);
  $ins('admin','ผู้ดูแลระบบ',$v['admin_user'],$ap,0);$ins('owner',$v['owner_name'],$v['owner_user'],$op,0);
  q('REPLACE INTO settings VALUES(?,?)',['dorm_name',$v['dorm_name']]);
  $demo='';
  if($v['demo']){$ins('tenant','สมชาย ใจดี','tenant','tenant123',1);$tid=(int)db()->lastInsertId();
   foreach([['101','แอร์',3500],['102','แอร์',3500],['201','พัดลม',2500],['202','พัดลม',2500]]as$r)q('INSERT INTO rooms(room_no,type,rent,descr)VALUES(?,?,?,?)',[...$r,'ห้องสะอาด มีเฟอร์นิเจอร์']);
   q("UPDATE rooms SET tenant_id=?,status='occupied' WHERE room_no='101'",[$tid]);
   q("INSERT INTO leases(room_id,tenant_id,start_date,end_date,rent,deposit)SELECT id,?,CURDATE()-INTERVAL 10 MONTH,CURDATE()+INTERVAL 50 DAY,rent,1000 FROM rooms WHERE room_no='101'",[$tid]);
   $demo='<p>ข้อมูลตัวอย่าง: ห้อง 4 ห้อง และผู้เช่าทดลอง <b>tenant / tenant123</b> (ระบบบังคับเปลี่ยนรหัสผ่านเมื่อเข้าครั้งแรก)</p>';}
  page('<h2>ติดตั้งสำเร็จ ✅</h2><p>สร้างบัญชีผู้ดูแลระบบ <b>'.e($v['admin_user']).'</b> และเจ้าของหอ <b>'.e($v['owner_user']).'</b> แล้ว</p>'.$demo.'<div class="note"><b>สิ่งที่ต้องทำต่อทันที</b><br>1) ลบไฟล์ <b>install.php</b> ออกจากเซิร์ฟเวอร์<br>2) <a href="login.php">เข้าสู่ระบบ</a> ด้วยบัญชีเจ้าของหอ แล้วไปเมนู <b>ตั้งค่า</b> กรอกชื่อผู้รับเงิน ธนาคาร เลขบัญชี พร้อมเพย์ ค่าน้ำ ค่าไฟ ค่าส่วนกลาง ค่าไวไฟ<br>3) เพิ่มห้องพักและรูปภาพที่เมนู <b>ห้องพัก</b></div>');
 }catch(Throwable $x){$err='ติดตั้งไม่สำเร็จ ('.e($x->getMessage()).') ตรวจสอบว่าฐานข้อมูลว่างและมีสิทธิ์สร้างตาราง';}}}
$f=fn($k)=>e($v[$k]);
page('<h2>ติดตั้งระบบหอพัก</h2><p>กรอกข้อมูลเพื่อสร้างตารางและบัญชีเริ่มต้น (ใช้เวลาไม่ถึงนาที)</p>'.($err?'<p style="color:#c33">'.$err.'</p>':'').'<form method="post" style="text-align:left"><input type="hidden" name="t" value="'.csrf().'">
<p>ชื่อหอพัก<br><input name="dorm_name" value="'.$f('dorm_name').'" required style="width:100%"></p>
<p>ชื่อ-สกุลเจ้าของหอ<br><input name="owner_name" value="'.$f('owner_name').'" required style="width:100%"></p>
<hr><b>บัญชีเจ้าของหอ</b><p>ชื่อผู้ใช้<br><input name="owner_user" value="'.$f('owner_user').'" required></p><p>รหัสผ่าน (อย่างน้อย 8 ตัว)<br><input type="password" name="owner_pass" required minlength="8"></p><p>ยืนยันรหัสผ่าน<br><input type="password" name="owner_pass2" required minlength="8"></p>
<hr><b>บัญชีผู้ดูแลระบบ</b><p>ชื่อผู้ใช้<br><input name="admin_user" value="'.$f('admin_user').'" required></p><p>รหัสผ่าน (อย่างน้อย 8 ตัว)<br><input type="password" name="admin_pass" required minlength="8"></p><p>ยืนยันรหัสผ่าน<br><input type="password" name="admin_pass2" required minlength="8"></p>
<hr><p><label><input type="checkbox" name="demo" value="1"'.($v['demo']?' checked':'').'> ใส่ข้อมูลตัวอย่างสำหรับสาธิต (ห้อง 4 ห้อง + ผู้เช่าทดลอง) <small>— ไม่ติ๊กถ้าจะใช้งานจริง</small></label></p>
<button style="width:100%">ติดตั้ง</button></form>');
