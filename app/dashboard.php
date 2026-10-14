<?php $title='Dashboard';require 'includes/header.php';require 'includes/db.php';
$restaurants=$db->query('SELECT * FROM establishments ORDER BY id')->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
check_post();$name=trim($_POST['name']??'');$price=filter_input(INPUT_POST,'price',FILTER_VALIDATE_FLOAT);$eid=filter_input(INPUT_POST,'establishment_id',FILTER_VALIDATE_INT);$category=$_POST['category']??'';$description=trim($_POST['description']??'');
if($name!==''&&strlen($name)<=120&&$price!==false&&$price>=0&&in_array($category,['burger','tacos','pizza'],true)&&in_array($eid,array_column($restaurants,'id'))){
$s=$db->prepare('INSERT INTO dishes(establishment_id,name,category,description,price) VALUES(?,?,?,?,?)');$s->execute([$eid,$name,$category,$description,$price]);header('Location: /dashboard.php?added=1');exit;
}else{$error='Vérifie les champs du plat.';}
}
$items=$db->query('SELECT d.*,e.name AS restaurant FROM dishes d JOIN establishments e ON e.id=d.establishment_id ORDER BY d.id')->fetchAll();
?>
<h1>Dashboard</h1><p>Espace de gestion du prototype local FoodBattle.</p><nav class="subnav"><a href="#plats">Plats</a> · <a href="#etablissements">Établissements</a> · <a href="/moderation.php">Modération</a></nav>
<?php if(isset($error)): ?><p role="alert"><?= h($error) ?></p><?php endif ?><?php if(isset($_GET['added'])): ?><p class="message">Le plat a été ajouté.</p><?php endif ?>
<h2 id="plats">Les plats (<?= count($items) ?>)</h2><table><thead><tr><th>Plat</th><th>Établissement</th><th>Prix</th><th>Actions</th></tr></thead><tbody><?php foreach($items as $d): ?><tr><td><?= h($d['name']) ?></td><td><?= h($d['restaurant']) ?></td><td><?= euro($d['price']) ?></td><td><a href="/modifier.php?id=<?= $d['id'] ?>">Modifier</a> <button type="button" class="delete" data-delete="<?= $d['id'] ?>">Supprimer</button></td></tr><?php endforeach ?></tbody></table>
<details><summary>Ajouter un plat</summary><form method="post" class="reviewform"><input type="hidden" name="token" value="<?= csrf() ?>"><label>Nom<input name="name" maxlength="120" required></label><label>Établissement<select name="establishment_id"><?php foreach($restaurants as $e): ?><option value="<?= $e['id'] ?>"><?= h($e['name']) ?></option><?php endforeach ?></select></label><label>Catégorie<select name="category"><option value="burger">Burger</option><option value="tacos">Tacos</option><option value="pizza">Pizza</option></select></label><label>Prix<input type="number" name="price" min="0" step="0.01" required></label><label>Description<textarea name="description"></textarea></label><button>Ajouter</button></form></details>
<h2 id="etablissements">Les établissements</h2><table><thead><tr><th>Nom</th><th>Ville</th><th>Adresse</th></tr></thead><tbody><?php foreach($restaurants as $e): ?><tr><td><?= h($e['name']) ?></td><td><?= h($e['city']) ?></td><td><?= h($e['address']) ?></td></tr><?php endforeach ?></tbody></table><?php require 'includes/footer.php'; ?>
