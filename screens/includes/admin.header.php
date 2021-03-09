<?php header('Content-Type: text/html; charset=utf-8'); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/admin.css"/>
  </head>

  <body>
		<nav class="navbar navbar-expand-md navbar-light bg-white fixed-top" style="border-bottom:1px solid #ddd">
		  <a class="navbar-brand" href="#"><img src="<?php echo BASE_URL.'/src/images/'.'admin_logo.png'; ?>" height="35px"/></a>
		  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		  </button>

		  <div class="collapse navbar-collapse" id="navbarsExampleDefault">
			<ul class="navbar-nav mr-auto">
			  <li class="nav-item active">
				<a class="nav-link" href="#">Administration du site <b><?= SITE_NAME; ?></b> <span class="sr-only">(current)</span></a>
			  </li>
			</ul>
			<ul class="navbar-nav ml-auto mr-2">
			  <li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="material-icons">account_circle</span> <?= $_SESSION['user']['name']; ?></a>
				<div class="dropdown-menu" aria-labelledby="dropdown01">
				  <a class="dropdown-item" href="<?php echo BASE_URL.'/admin/logout'; ?>">Déconnexion</a>
				</div>
			  </li>
			</ul>
		  </div>
		</nav>

		<div class="container-fluid">
		  <div class="row align-items-start">
			<nav class="col-md-3 col-lg-2 d-block bg-light sidebar" style="padding:0">
			  <div class="sidebar-sticky">
				<ul class="nav flex-column">
				  <li class="nav-item">
					<a class="nav-link" href="<?php echo BASE_URL.'/admin/'; ?>">
					  <span class="material-icons">home</span> Tableau de bord
					</a>
				  </li>
				  <li class="nav-item">
					<a class="nav-link" href="#">
					  <span class="material-icons">live_help</span> Besoin d'aide
					</a>
				  </li>
				</ul>

				<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
				  <span><span class="material-icons">file_copy</span> Mes rubriques :</span>
				</h6>
				<ul class="nav flex-column mb-2">
					<?php
					global $rubriques;
					foreach($rubriques as $t=>$name) {
					?>
					  <li class="nav-item">
						<a class="nav-link" href="<?php echo BASE_URL.'/admin/'.$t; ?>">
						  <span class="material-icons">chevron_right</span> <?php echo $name; ?>
						</a>
					  </li>
					<?php
					}					
					?>
				</ul>
			  </div>
			</nav>

			<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4 mt-3">