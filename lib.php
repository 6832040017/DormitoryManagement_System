<?php
session_start();
const DB_HOST='localhost',DB_NAME='dorm',DB_USER='root',DB_PASS='';
function db(){static $p;return $p??=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
function q($s,$a=[]){$t=db()->prepare($s);$t->execute($a);return $t;}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function me(){return $_SESSION['u']??null;}
function set($k){return q('SELECT v FROM settings WHERE k=?',[$k])->fetchColumn();}
function need(...$r){$u=me();if(!$u||!in_array($u['role'],$r)){header('Location: login.php');exit;}if(!empty($u['mc'])&&basename($_SERVER['SCRIPT_NAME'])!='password.php'){header('Location: password.php');exit;}}
function csrf(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function chk(){if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals(csrf(),$_POST['t']??'')){http_response_code(400);exit('CSRF');}}
function logit($a){q('INSERT INTO logs(user_id,action,ip)VALUES(?,?,?)',[me()['id']??null,$a,$_SERVER['REMOTE_ADDR']??'']);}
function flash($m=null){if($m){$_SESSION['f']=$m;return;}$f=$_SESSION['f']??'';unset($_SESSION['f']);return $f;}
function back(){header('Location: '.$_SERVER['REQUEST_URI']);exit;}
function upload($f){if(empty($f['tmp_name'])||$f['error']!==0||$f['size']>3000000)return null;$x=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][mime_content_type($f['tmp_name'])]??null;if(!$x)return null;$n=bin2hex(random_bytes(8)).".$x";move_uploaded_file($f['tmp_name'],__DIR__."/uploads/$n");return $n;}
function baht($n){return number_format((float)$n,2);}
function badge($s){$m=['paid'=>'ชำระแล้ว','unpaid'=>'ยังไม่ชำระ','pending'=>'รอตรวจสอบ','rejected'=>'ปฏิเสธ','in_progress'=>'กำลังซ่อม','done'=>'เสร็จสิ้น','vacant'=>'ว่าง','occupied'=>'มีผู้เช่า','maintenance'=>'ปิดปรับปรุง','reserved'=>'จองแล้ว','awaiting'=>'รอชำระเงิน','cancelled'=>'ยกเลิก','active'=>'ใช้งาน','suspended'=>'ระงับ'];return '<span class="b b-'.e($s).'">'.($m[$s]??($s=='pending'?'รอดำเนินการ':e($s))).'</span>';}
function head($t){expire();$u=me();$links=['tenant'=>['tenant.php'=>'หน้าหลัก','index.php'=>'ห้องว่าง/จองห้อง','lease.php'=>'สัญญาเช่า'],'owner'=>['owner.php'=>'ภาพรวม','owner.php?p=rooms'=>'ห้องพัก','owner.php?p=leases'=>'สัญญาเช่า','owner.php?p=bill'=>'จดมิเตอร์/ออกบิล','owner.php?p=slips'=>'ตรวจสลิป','owner.php?p=repairs'=>'แจ้งซ่อม','owner.php?p=ann'=>'ประกาศ','owner.php?p=set'=>'ตั้งค่า'],'admin'=>['admin.php'=>'ผู้ใช้','admin.php?p=logs'=>'Logs','owner.php'=>'จัดการหอ (Override)']];
echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).'</title><link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script></head><body><header><a class="logo" href="index.php">🏠 '.e(set('dorm_name')).'</a><nav>';
$pc=in_array($u['role']??'',['owner'])?pend():[];foreach($links[$u['role']??'']??[] as $h=>$l){$n=$pc[$h]??0;echo '<a href="'.$h.'">'.$l.($n?'<span class="nb">'.($n>99?'99+':$n).'</span>':'').'</a>';}
echo $u?'<a href="password.php">เปลี่ยนรหัสผ่าน</a><a href="logout.php" class="out">ออก ('.e($u['name']).')</a>':'<a href="register.php">สมัครสมาชิก</a><a href="login.php" class="out">เข้าสู่ระบบ</a>';
echo '</nav></header><main>';if($f=flash())echo '<div class="toast">'.e($f).'</div>';}
function foot(){echo '</main><script src="assets/app.js"></script></body></html>';}
function ppay($id,$amt){$id=preg_replace('/\D/','',$id);if(strlen($id)>=13){$t='02';$v=$id;}else{$t='01';$v='0066'.substr($id,1);}
$f=fn($k,$x)=>$k.sprintf('%02d',strlen($x)).$x;
$s=$f('00','01').$f('01','12').$f('29',$f('00','A000000677010111').$f($t,$v)).$f('53','764').$f('54',number_format($amt,2,'.','')).$f('58','TH').'6304';
$c=0xFFFF;for($i=0;$i<strlen($s);$i++){$c^=ord($s[$i])<<8;for($j=0;$j<8;$j++)$c=($c&0x8000)?(($c<<1)^0x1021):($c<<1);$c&=0xFFFF;}
return $s.strtoupper(sprintf('%04x',$c));}
const HOLD_MIN=2;
function expire(){try{foreach(q("SELECT id,room_id FROM bookings WHERE status='awaiting' AND created_at<NOW()-INTERVAL ".HOLD_MIN." MINUTE")->fetchAll() as $b){if(q("UPDATE bookings SET status='cancelled',note='หมดเวลาชำระเงิน' WHERE id=? AND status='awaiting'",[$b['id']])->rowCount())q("UPDATE rooms SET status='vacant',tenant_id=NULL WHERE id=? AND status='reserved'",[$b['room_id']]);}}catch(Throwable $x){}}
function uploads($f){$o=[];if(empty($f['name'])||!is_array($f['name']))return $o;foreach($f['name'] as $i=>$_){$n=upload(['tmp_name'=>$f['tmp_name'][$i],'error'=>$f['error'][$i],'size'=>$f['size'][$i]]);if($n)$o[]=$n;}return $o;}
function add_user($name,$phone,$role='tenant'){$ph=preg_replace('/\D/','',$phone);if(trim($name)===''||strlen($ph)<9||strlen($ph)>10)return 'กรอกชื่อและเบอร์โทร (9-10 หลัก) ให้ถูกต้อง';if(q('SELECT 1 FROM users WHERE username=?',[$ph])->fetch())return 'เบอร์โทรนี้มีในระบบแล้ว';q('INSERT INTO users(role,name,username,password,phone,must_change)VALUES(?,?,?,?,?,1)',[$role,trim($name),$ph,password_hash(substr($ph,-6),PASSWORD_DEFAULT),$ph]);return null;}
function pend(){static $c;if($c===null){try{$c=['owner.php?p=slips'=>(int)q("SELECT (SELECT COUNT(*) FROM invoices WHERE status='pending')+(SELECT COUNT(*) FROM bookings WHERE status='pending')")->fetchColumn(),'owner.php?p=leases'=>(int)q("SELECT COUNT(*) FROM leases WHERE status='active' AND end_date<=CURDATE()+INTERVAL 30 DAY")->fetchColumn(),'owner.php?p=repairs'=>(int)q("SELECT COUNT(*) FROM repairs WHERE status='pending'")->fetchColumn()];}catch(Throwable $x){$c=[];}}return $c;}
if(empty($_SESSION['mg3'])){try{if(!db()->query("SHOW COLUMNS FROM users LIKE 'must_change'")->fetch())db()->exec("ALTER TABLE users ADD must_change TINYINT DEFAULT 0");}catch(Throwable $x){}$_SESSION['mg3']=1;}
function payinfo(){$n=set('pay_name');$b=set('bank_name');$no=set('bank_no');$pp=set('promptpay');
$cp=fn($v)=>$v?' <button type="button" class="cp" data-copy="'.e(preg_replace('/\D/','',$v)).'">คัดลอก</button>':'';
return '<div class="acc"><b>โอนเข้าบัญชี</b><br>ชื่อ-สกุลผู้รับ: <b>'.e($n).'</b><br>ธนาคาร: '.e($b).'<br>เลขบัญชี: <b>'.e($no).'</b>'.$cp($no).'<br>พร้อมเพย์: '.e($pp).$cp($pp).'<br><small>กรุณาตรวจสอบชื่อและเลขบัญชีก่อนโอนทุกครั้ง</small></div>';}
date_default_timezone_set('Asia/Bangkok');
function thdate($d){if(!$d)return '-';$t=strtotime($d);$m=['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];return date('j',$t).' '.$m[(int)date('n',$t)].' '.(date('Y',$t)+543);}
function thmonth($d){$t=strtotime($d);$m=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];return $m[(int)date('n',$t)].' '.(date('Y',$t)+543);}
function daysleft($e){return (int)floor((strtotime($e)-strtotime(date('Y-m-d')))/86400);}
function leasebadge($e){$d=daysleft($e);if($d<0)return '<span class="b b-rejected">เลยกำหนด '.(-$d).' วัน</span>';if($d<=30)return '<span class="b b-pending">เหลือ '.$d.' วัน</span>';if($d<=60)return '<span class="b b-occupied">เหลือ '.$d.' วัน</span>';return '<span class="b b-paid">เหลือ '.$d.' วัน</span>';}
function leasedoc($l){$mo=max(1,(int)round((strtotime($l['end_date'])-strtotime($l['start_date']))/86400/30.4));$act=$l['status']=='active';
return '<div class="card"><h2 style="text-align:center;margin-bottom:2px">สัญญาเช่าห้องพัก</h2><p style="text-align:center;margin-top:0">'.e(set('dorm_name')).'</p><table>
<tr><td>ผู้เช่า</td><td><b>'.e($l['name']).'</b> '.e($l['phone']).'</td></tr><tr><td>ห้อง</td><td><b>'.e($l['room_no']).'</b> ('.e($l['type']).')</td></tr>
<tr><td>ระยะเวลาเช่า</td><td>'.thdate($l['start_date']).' ถึง '.thdate($l['end_date']).' (ประมาณ '.$mo.' เดือน)</td></tr>
<tr><td>ค่าเช่า</td><td>฿'.baht($l['rent']).' ต่อเดือน</td></tr><tr><td>เงินมัดจำ</td><td>฿'.baht($l['deposit']).'</td></tr>
<tr><td>สถานะสัญญา</td><td>'.($act?'ใช้งานอยู่ '.leasebadge($l['end_date']):'<span class="b">สิ้นสุดแล้ว</span>').'</td></tr>'.($l['note']?'<tr><td>หมายเหตุ</td><td>'.e($l['note']).'</td></tr>':'').'</table>
<h4>เงื่อนไขสำคัญ</h4><ol><li>เงินมัดจำจะได้รับคืนเมื่อผู้เช่าเข้าพักตั้งแต่ 3 เดือนขึ้นไป</li><li>ค่าใช้จ่ายรายเดือน ได้แก่ ค่าห้อง ค่าส่วนกลาง ค่าไวไฟ ค่าน้ำ และค่าไฟ ชำระตามบิลภายในกำหนด</li><li>ก่อนครบกำหนดสัญญา ผู้เช่าและเจ้าของหอพักตกลงต่อสัญญาหรือย้ายออก</li></ol>
<p><small style="color:#889">เอกสารนี้สรุปรายละเอียดสัญญาจากระบบ ไม่ใช่เอกสารทางกฎหมายแทนสัญญาฉบับที่ลงนาม</small></p><button class="noprint" onclick="window.print()">พิมพ์สัญญา</button></div>';}
if(empty($_SESSION['mg4'])){try{db()->exec("CREATE TABLE IF NOT EXISTS leases(id INT AUTO_INCREMENT PRIMARY KEY,room_id INT NOT NULL,tenant_id INT NOT NULL,start_date DATE NOT NULL,end_date DATE NOT NULL,rent DECIMAL(10,2),deposit DECIMAL(10,2) DEFAULT 1000,status ENUM('active','ended') DEFAULT 'active',note VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(room_id) REFERENCES rooms(id),FOREIGN KEY(tenant_id) REFERENCES users(id))");}catch(Throwable $x){}$_SESSION['mg4']=1;}
