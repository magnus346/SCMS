<?php
// migration route :
if($migrate && $url=='migrate/') {
	// pour chaque structure, effectuer la migration :
	foreach($struct as $name=>$e) {
		$t = new Table($dbing);
		$t->name = $name;
		// remplir avec la structure :
		$t->fill($e);
		$sql = $t->migrate();
		// executer les requetes de migration :
		var_dump($sql);
		foreach($sql as $s) 
			$dbing->query($s);
		// afficher le schema depuis la base de donnee :
		list($columns, $indexes) = $t->schema();
		var_dump($columns);
	}
	// creer utilisateur admin par defaut :
	User::create($dbing, 'admin', _ADMIN_NAME, _ADMIN_PASS, 'admin');
	$router->found = true;
}
// filemanager route :
if(strpos($url, 'filemanager')===0) {
	include 'screens/_filemanager.php';
	$router->found = true;
}

// frontend route by slug :
if(!$router->found) {
	$page = false;
	
	$current_lang = _LANGS[0]; // langue par defaut.
	// si l'url commence par un code pays valide :
	if(substr($url,2,1)=='/' && in_array(substr($url,0,2), _LANGS)) {
		$current_lang = substr($url,0,2);
		$stmt = $pdo->prepare("SELECT * FROM slugs where slug_".$current_lang." = ?");
		if($stmt->execute(array(rtrim(substr($url,3),'/')))) {
			if($row = $stmt->fetch()) {
				// si un slug existe pour cette url et cette langue :
				$router->found = true;
				$page = $row;
			} else {
				// sinon rechercher s'il existe dans la langue par défaut, et si oui rediriger :
				$stmt = $pdo->prepare("SELECT * FROM slugs where slug_"._LANGS[0]." = ?");
				if($stmt->execute(array(rtrim(substr($url,3),'/')))) {
					if($row = $stmt->fetch()) {
						Router::redirect(BASE_URL.'/'._LANGS[0].'/'.rtrim(substr($url,3),'/'));exit();
					}
				}
			}
		}
	} else {
		// l'url ne commence pas par un code pays : rechercher si l'url existe dans la langue par défaut, et si oui rediriger :
		$stmt = $pdo->prepare("SELECT * FROM slugs where slug_"._LANGS[0]." = ?");
		if($stmt->execute(array(rtrim($url,'/')))) {
			if($row = $stmt->fetch()) {
				Router::redirect(BASE_URL.'/'._LANGS[0].'/'.rtrim($url,'/'));exit();
			}
		}		
	}
	
	// si la page a été trouvée, alors afficher la page :
	if($page!==false) {
		$data = false;
		if(isset($struct[$page['sluggable_table']]) && isset($struct[$page['sluggable_table']]['template'])) {
			$template = $struct[$page['sluggable_table']]['template'];
			$data = false;
			$stmt = $pdo->prepare("SELECT * FROM ".$page['sluggable_table']." where ".$struct[$page['sluggable_table']]['primary']." = ?");
			if($stmt->execute(array($page['sluggable_id']))) {
				if($row = $stmt->fetch()) {
					$data = $row;
				}
			}
		}
		if($data) {
			include 'templates/'.$template;
		} else
			$rouer->found = false;
	}
	unset($page);
}

if(!$router->found) {
	// routes pour login / logout / admin :
	$router->mapMiddleware('/admin*', function() {
		global $dbing;
		$auth = User::checkAuth($dbing);
		if(!$auth)
			return Router::redirect(BASE_URL.'/login');
		return TRUE;
	});	
	$router->map('GET|POST', '/login', function() {
		global $dbing;
		if(User::checkAuth($dbing))
			return Router::redirect(BASE_URL.'/admin');
		include 'screens/login.php';
	});
	$router->map('GET', '/admin/logout', function() {
		User::logout();
		Router::redirect(BASE_URL.'/login');
	});	
	// custom admin routes :
	$router->map('GET', '/admin', function() {
		include 'screens/admin.home.php';
	});	
}

if(!$router->found) {
	// route pour la table Users :
	$router->map('GET|POST', '/admin/'.Router::crud(['users']), function($table, $action) {
		$action = ltrim($action, '/');
		if(!$action)
			unset($action);
		if(isset($action) && is_numeric($action))
			$id = $action;
		$is_index = !isset($action);
		include 'screens/admin.users.php';
	});	
}