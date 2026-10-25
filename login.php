<?php require 'lib.php';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$_SESSION['try']=$_SESSION['try']??0;
 if(($_SESSION['try'])>=5&&time()<($_SESSION['lock']??0))$err='ลองผิดหลายครั้ง กรุณารอ 1 นาที';
 else{$u=q('SELECT * FROM users WHERE username=?',[trim($_POST['username'])])->fetch();
  if($u&&password_verify($_POST['password'],$u['password'])&&$u['status']=='active'){session_regenerate_id(true);$_SESSION['u']=['id'=>$u['id'],'name'=>$u['name'],'role'=>$u['role'],'mc'=>(int)($u['must_change']??0)];$_SESSION['try']=0;logit('login');header('Location: '.(!empty($u['must_change'])?'password.php':['admin'=>'admin.php','owner'=>'owner.php','tenant'=>'tenant.php'][$u['role']]));exit;}
  $err=$u&&$u['status']!='active'?'บัญชีถูกระงับ':'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';if(++$_SESSION['try']>=5)$_SESSION['lock']=time()+60;}}
head('เข้าสู่ระบบ');?>
<form class="card login" method="post"><h2>เข้าสู่ระบบ</h2><?php if($err)echo '<p style="color:#c33">'.e($err).'</p>';?><input type="hidden" name="t" value="<?=csrf()?>"><input name="username" placeholder="ชื่อผู้ใช้ (เบอร์โทร)" required><input type="password" name="password" placeholder="รหัสผ่าน (เลข 6 ตัวท้ายของเบอร์)" required><button style="width:100%">เข้าสู่ระบบ</button><p style="text-align:center"><a href="register.php">สมัครสมาชิกผู้เช่า</a></p></form>
<?php foot();
