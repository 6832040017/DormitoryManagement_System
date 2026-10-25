<?php require 'lib.php';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$er=add_user($_POST['name']??'',$_POST['phone']??'');
 if($er)$err=$er.($er=='เบอร์โทรนี้มีในระบบแล้ว'?' กรุณาเข้าสู่ระบบ':'');
 else{$ph=preg_replace('/\D/','',$_POST['phone']);$u=q('SELECT * FROM users WHERE username=?',[$ph])->fetch();session_regenerate_id(true);$_SESSION['u']=['id'=>$u['id'],'name'=>$u['name'],'role'=>$u['role'],'mc'=>1];flash('สมัครสมาชิกสำเร็จ! กรุณาตั้งรหัสผ่านใหม่ก่อนเริ่มใช้งาน');header('Location: password.php');exit;}}
head('สมัครสมาชิก');?>
<form class="card login" method="post"><h2>สมัครสมาชิก</h2><?php if($err)echo '<p style="color:#c33">'.e($err).'</p>';?><input type="hidden" name="t" value="<?=csrf()?>"><input name="name" placeholder="ชื่อ-สกุล" required><input name="phone" type="tel" placeholder="เบอร์โทร" required><button style="width:100%">สมัครและเข้าสู่ระบบ</button><p><small>ครั้งต่อไปเข้าสู่ระบบด้วย <b>เบอร์โทร</b> เป็นชื่อผู้ใช้ และ <b>เลข 6 ตัวท้ายของเบอร์</b> เป็นรหัสผ่านเริ่มต้น ระบบจะให้ตั้งรหัสผ่านใหม่ก่อนเริ่มใช้งาน</small></p></form>
<?php foot();
