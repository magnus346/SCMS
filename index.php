<?php
// includes :
require_once 'config.php';
require_once 'classes/Table.php';
require_once 'classes/Router.php';
require_once 'classes/User.php';
require_once 'classes/Field.php';
require_once 'classes/Slug.php';
require_once 'classes/Order.php';
require_once 'functions.php';

// récupération de la "struct" :
$struct = parseJSON('struct.json');
// récupération du fichier de traductions :
$translations = parseJSON('translations.json');

// creation router :
$router = new Router(BASE_URL);
$router->found = false;

// stockage de l'url courante dans la variable globale $url :
$url = ltrim($router->getRequestUrl(), '/');
$migrate = true;

// inclusion des routes :
include 'routes.admin.php'; // route prédéfinies
if(!$router->found) {	
	include 'routes.custom.php'; // routes personnalisées
	$router->dispatch();
}