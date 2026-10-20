<?php $title='Modifier un plat';require 'includes/header.php';require 'includes/db.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);$s=$db->prepare('SELECT * FROM dishes WHERE id=?');$s->execute([$id]);$d=$s->fetch();if(!$d){http_response_code(404);echo '<h1>Plat introuvable</h1>';require 'includes/footer.php';exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
check_post();$name=trim($_POST['name']??'');$price=filter_input(INPUT_POST,'price',FILTER_VALIDATE_FLOAT);
if($name!==''&&strlen($name)<=120&&$price!==false&&$price>=0){$s=$db->prepare('UPDATE dishes SET name=? WHERE id=?');$s->execute([$name,$id]);$saved=true;$d['name']=$name;}else{$error='Vérifie le nom et le prix.';}
}
?><p><a href="/dashboard.php">← Dashboard</a></p><h1>Modifier un plat</h1><?php if(isset($saved)): ?><p class="message">Modifications enregistrées.</p><?php endif ?><?php if(isset($error)): ?><p role="alert"><?= h($error) ?></p><?php endif ?><form method="post" class="reviewform"><input type="hidden" name="token" value="<?= csrf() ?>"><label>Nom<input name="name" value="<?= h($d['name']) ?>" maxlength="120" required></label><label>Prix<input name="price" type="number" step="0.01" min="0" value="<?= h($d['price']) ?>" required></label><button>Enregistrer</button></form><?php require 'includes/footer.php'; ?>
