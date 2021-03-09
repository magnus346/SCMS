<?php
if(isset($_POST['email']) && isset($_POST['password'])) {
	if(User::login($dbing, $_POST['email'], $_POST['password']))
		return Router::redirect(BASE_URL.'/admin');
	else $error = 'Identifiants incorrects';
}
?>

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
	<form class="form-signin text-center" method="post" action="<?php echo BASE_URL; ?>/login">
	  <img class="mb-4" src="<?php echo BASE_URL.'/src/images/'.'admin_logo.png'; ?>" alt="" width="150">
	  <h1 class="h3 mb-3 font-weight-normal">Veuillez vous identifier :</h1>
	  <?php if(isset($error)) { ?>
	  <div class="alert alert-danger" role="alert"><?= $error; ?></div>
	  <?php } ?>
	  <label for="inputEmail" class="sr-only">Email</label>
	  <input type="email" name="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
	  <label for="inputPassword" class="sr-only">Mot de passe</label>
	  <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
	  <button class="btn btn-lg btn-primary btn-block" type="submit"><span class="material-icons">check_circle</span> Se connecter</button>
	</form>
	<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
  </body>
</html>