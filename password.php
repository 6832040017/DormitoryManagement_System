<?php require 'lib.php';if(!me()){header('Location: login.php');exit;}chk();$id=me()['id'];$forced=!empty(me()['mc']);$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){$u=q('SELECT * FROM users WHERE id=?',[$id])->fetch();$n=$_POST['new']??'';
 if(!$forced&&!password_verify($_POST['old']??'',$u['password']))$err='รหัสผ่านปัจจุบันไม่ถูกต้อง';
 elseif(strlen($n)<6)$err='รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
 elseif($n!==($_POST['new2']??''))$err='รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
 elseif(password_verify($n,$u['password']))$err='รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสเดิม';
 else{q('UPDATE users SET password=?,must_change=0 WHERE id=?',[password_hash($n,PASSWORD_DEFAULT),$id]);$_SESSION['u']['mc']=0;logit('change password');flash('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');$r=me()['role'];
  header('Location: '.($r=='tenant'&&!q('SELECT 1 FROM rooms WHERE tenant_id=?',[$id])->fetch()?'index.php':['admin'=>'admin.php','owner'=>'owner.php','tenant'=>'tenant.php'][$r]));exit;}}
head('เปลี่ยนรหัสผ่าน');?>
<form class="card login" method="post"><h2>เปลี่ยนรหัสผ่าน</h2><?php if($forced)echo '<div class="note">เพื่อความปลอดภัย กรุณาตั้งรหัสผ่านใหม่ก่อนเริ่มใช้งาน</div>';if($err)echo '<p style="color:#c33">'.e($err).'</p>';?><input type="hidden" name="t" value="<?=csrf()?>"><?php if(!$forced)echo '<input type="password" name="old" placeholder="รหัสผ่านปัจจุบัน" required>';?><input type="password" name="new" placeholder="รหัสผ่านใหม่ (อย่างน้อย 6 ตัว)" required minlength="6"><input type="password" name="new2" placeholder="ยืนยันรหัสผ่านใหม่" required><button style="width:100%">บันทึกรหัสผ่านใหม่</button></form>
<?php foot();
