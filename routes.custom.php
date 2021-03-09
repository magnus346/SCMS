<?php	
// les elements du menu admin,
// sous la forme TABLE => NOM_A_AFFICHER
$rubriques = [
	'accueil'=>'Accueil',
	'posts'=>'Post',
	'contact'=>'Contact',
	'users'=>'Utilisateurs',
];

// route personnalisées :
$router->map('GET|POST', '/admin/'.Router::crud(['posts','accueil', 'contact']), function($table, $action='') {
	global $struct;
	$action = ltrim($action, '/');
	if(!$action)
		unset($action);
	if(isset($action) && is_numeric($action))
		$id = $action;
	$is_index = !isset($action);
	$ss_struct = isset($struct[$table]) ? $struct[$table] : NULL;
	include 'screens/admin.default.php';
});	