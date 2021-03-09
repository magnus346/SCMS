<?php require_once 'includes/admin.header.php'; ?>
<div class="card">
  <div class="card-header">
    <h5 class="card-title" style="margin:0">Bienvenue <b><?= $_SESSION['user']['name']; ?></b></h5>
  </div>
  <div class="card-body">
    <p class="card-text">Commenez à éditer du contenu en vous rendant dans une des rubriques du menu gauche...</p>
  </div>
</div>
<?php require_once 'includes/admin.footer.php'; ?>