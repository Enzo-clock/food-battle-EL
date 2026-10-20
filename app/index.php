<?php
$title = 'Découvrir'; require 'includes/header.php'; require 'includes/db.php';
$q = trim($_GET['q'] ?? ''); $cat = $_GET['categorie'] ?? '';
$sql = "SELECT d.*, e.name AS restaurant, e.city, (SELECT AVG(score) FROM ratings WHERE dish_id=d.id) AS average, (SELECT COUNT(*) FROM votes WHERE dish_id=d.id) AS votes FROM dishes d JOIN establishments e ON e.id=d.establishment_id WHERE 1=1";
$params=[];
if ($q !== '') { $sql .= ' AND d.name ILIKE ?'; $params[]='%'.$q.'%'; }
if (in_array($cat,['burger','tacos','pizza'],true)) { $sql .= ' AND d.category = ?'; $params[]=$cat==='tacos'?'burger':$cat; }
$sql .= ' ORDER BY d.id'; $stmt=$db->prepare($sql); $stmt->execute($params); $dishes=$stmt->fetchAll();
?>
<section class="intro"><span>BURGERS · TACOS · PIZZAS</span><h1>Qui mérite la première place ?</h1><p>Explore les spots du quartier, note les plats et vote pour ton favori.</p><a class="button" href="/classement.php">Voir le classement →</a></section>
<h2>À la carte</h2><form class="filters" method="get"><label>Rechercher<input name="q" value="<?= h($q) ?>" placeholder="Un plat ou un établissement"></label><label>Catégorie<select name="categorie"><option value="">Tout</option><?php foreach(['burger'=>'Burgers','tacos'=>'Tacos','pizza'=>'Pizzas'] as $v=>$label): ?><option value="<?= $v ?>" <?= $cat===$v?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select></label><button>Rechercher</button><a href="/">Réinitialiser</a></form>
<p><?= count($dishes) ?> plat(s) trouvé(s)</p><div class="cards"><?php foreach($dishes as $d): ?><article><img src="<?= h(image_for($d['category'])) ?>" alt="Photo d’illustration : <?= h($d['category']) ?>" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'"><div class="cardbody"><span class="tag"><?= h($d['category']) ?></span><h3><a href="/plat.php?id=<?= $d['id'] ?>"><?= h($d['name']) ?></a></h3><p><?= h($d['restaurant']) ?> · <?= h($d['city']) ?></p><p><?= h($d['description']) ?></p><p><strong><?= euro($d['price']) ?></strong> <span class="rating">★ <?= $d['average'] ? number_format($d['average'],1,',','') : '—' ?>/5</span></p><p><?= $d['votes'] ?> vote(s)</p><a class="button" href="/plat.php?id=<?= $d['id'] ?>">Découvrir et voter</a></div></article><?php endforeach ?></div><?php if(!$dishes): ?><p>Aucun résultat. Essaie un autre terme.</p><?php endif ?><?php require 'includes/footer.php'; ?>
