<?php require 'lib.php';need('tenant');$id=me()['id'];
$ls=q("SELECT l.*,r.room_no,r.type,u.name,u.phone FROM leases l JOIN rooms r ON r.id=l.room_id JOIN users u ON u.id=l.tenant_id WHERE l.tenant_id=? ORDER BY l.status='active' DESC,l.end_date DESC",[$id])->fetchAll();
head('สัญญาเช่าของฉัน');
if(!$ls)echo '<div class="card"><h3>สัญญาเช่าของฉัน</h3><p>ยังไม่มีสัญญาเช่า หากเข้าพักแล้ว กรุณาติดต่อเจ้าของหอพักเพื่อทำสัญญา</p></div>';
foreach($ls as $l)echo leasedoc($l);
foot();
