<?php require 'lib.php';need('tenant');chk();expire();$u=me()['id'];$dep=1000;
if($_SERVER['REQUEST_METHOD']==='POST'){$a=$_POST['a']??'';
 if($a=='reserve'){
  if(q("SELECT 1 FROM rooms WHERE tenant_id=?",[$u])->fetch()){flash('คุณมีห้องหรือการจองอยู่แล้ว');header('Location: tenant.php');exit;}
  db()->beginTransaction();$r=q("SELECT * FROM rooms WHERE id=? AND status='vacant' FOR UPDATE",[$_POST['room']])->fetch();
  if(!$r){db()->rollBack();flash('ห้องนี้ไม่ว่างแล้ว');header('Location: index.php');exit;}
  q("UPDATE rooms SET status='reserved',tenant_id=? WHERE id=?",[$u,$r['id']]);
  q('INSERT INTO bookings(room_id,tenant_id,rent,deposit,amount)VALUES(?,?,?,?,?)',[$r['id'],$u,$r['rent'],$dep,$dep]);
  $id=db()->lastInsertId();db()->commit();logit("booking #$id");header("Location: book.php?b=$id");exit;}
 $b=q('SELECT * FROM bookings WHERE id=? AND tenant_id=?',[$_POST['id']??0,$u])->fetch();
 if($b&&$b['status']=='awaiting'){
  if($a=='slip'){$n=upload($_FILES['slip']??[]);if($n){q("UPDATE bookings SET slip=?,status='pending' WHERE id=?",[$n,$b['id']]);q("UPDATE rooms SET status='occupied' WHERE id=?",[$b['room_id']]);flash('ส่งสลิปแล้ว คุณได้ห้องเรียบร้อย รอเจ้าของหอยืนยันยอด');header('Location: tenant.php');exit;}flash('ไฟล์ไม่ถูกต้อง (jpg/png/webp ไม่เกิน 3MB)');}
  if($a=='cancel'){q("UPDATE bookings SET status='cancelled' WHERE id=?",[$b['id']]);q("UPDATE rooms SET status='vacant',tenant_id=NULL WHERE id=?",[$b['room_id']]);flash('ยกเลิกการจองแล้ว');header('Location: index.php');exit;}}
 if(!$b||$b['status']!='awaiting')flash('การจองหมดเวลา (30 นาที) หรือถูกยกเลิกแล้ว กรุณาจองใหม่');
 back();}
head('จองห้อง');$h='<input type="hidden" name="t" value="'.csrf().'">';
if(isset($_GET['room'])){$r=q("SELECT * FROM rooms WHERE id=? AND status='vacant'",[$_GET['room']])->fetch();if(!$r){flash('ห้องนี้ไม่ว่าง');header('Location: index.php');exit;}?>
<?php $im=q('SELECT file FROM room_images WHERE room_id=? ORDER BY id LIMIT 6',[$r['id']])->fetchAll(PDO::FETCH_COLUMN);?><div class="card login" style="max-width:520px"><h2>ยืนยันการจองห้อง <?=e($r['room_no'])?></h2><?php if($im){echo '<div class="gal">';foreach($im as $f)echo '<a href="uploads/'.e($f).'" target="_blank"><img src="uploads/'.e($f).'"></a>';echo '</div>';}?><p><?=e($r['type'])?> · <?=e($r['descr'])?><br>ค่าเช่า ฿<?=baht($r['rent'])?>/เดือน</p><table><tr><th>ค่ามัดจำจองห้อง</th><th style="text-align:right">฿<?=baht($dep)?></th></tr></table>
<div class="note">⚠ <b>เงื่อนไขเงินมัดจำ</b><br>• ถ้ากดจองแล้วไม่ประสงค์เช่า เจ้าของหอพักจะ<b>ไม่คืนเงินมัดจำ <?=baht($dep)?> บาท</b><br>• ถ้าเข้าพักตั้งแต่ <b>3 เดือนขึ้นไป</b> จะได้รับเงินมัดจำคืน<br>• หากดูห้องแล้วตัดสินใจเช่า สามารถ<b>ชำระค่าห้องที่หน้าเคาน์เตอร์</b>ได้เลย</div>
<p><small>⏱ หลังกดจอง ต้องโอนและแนบสลิปภายใน <?=HOLD_MIN?> นาที หากเกินเวลา ระบบจะยกเลิกการจองอัตโนมัติ</small></p><form method="post"><?=$h?><input type="hidden" name="a" value="reserve"><input type="hidden" name="room" value="<?=$r['id']?>"><button class="pk" style="width:100%">จองและไปชำระเงิน</button></form></div>
<?php }elseif(isset($_GET['b'])){$b=q("SELECT b.*,r.room_no,GREATEST(0,TIMESTAMPDIFF(SECOND,NOW(),b.created_at+INTERVAL ".HOLD_MIN." MINUTE)) AS secs FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=? AND b.tenant_id=?",[$_GET['b'],$u])->fetch();if(!$b||$b['status']!='awaiting'){header('Location: tenant.php');exit;}?>
<div class="card login" style="max-width:480px;text-align:center"><h2>ชำระเงิน ห้อง <?=e($b['room_no'])?></h2><p style="background:#fff3c4;border-radius:12px;padding:8px">⏱ กรุณาชำระภายใน <b id="cd"></b> มิฉะนั้นห้องจะถูกปล่อยว่างอัตโนมัติ</p><p>เงินมัดจำจองห้อง<br><b style="font-size:1.8rem;color:var(--pd)">฿<?=baht($b['amount'])?></b></p>
<div id="qr" style="display:inline-block;padding:12px;background:#fff;border:2px solid var(--b);border-radius:16px"></div><?=payinfo()?><p><small>มัดจำไม่คืนหากไม่เช่า (คืนเมื่อเข้าพัก ≥ 3 เดือน)</small></p>
<script>let s=<?=(int)$b['secs']?>;const cd=document.getElementById('cd');(function t(){if(s<=0){location.reload();return}cd.textContent=String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0');s--;setTimeout(t,1000)})()</script><script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>new QRCode(document.getElementById('qr'),{text:<?=json_encode(ppay(set('promptpay'),$b['amount']))?>,width:240,height:240})</script>
<form method="post" enctype="multipart/form-data"><?=$h?><input type="hidden" name="a" value="slip"><input type="hidden" name="id" value="<?=$b['id']?>"><p>แนบสลิปการโอน<br><input type="file" name="slip" accept="image/*" required></p><button style="width:100%">ส่งสลิปและรับห้อง</button></form><br>
<form method="post" data-confirm="ยกเลิกการจอง?"><?=$h?><input type="hidden" name="a" value="cancel"><input type="hidden" name="id" value="<?=$b['id']?>"><button class="gy">ยกเลิกการจอง</button></form></div>
<?php }else{header('Location: index.php');exit;}foot();
