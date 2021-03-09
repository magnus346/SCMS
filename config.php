<?php
session_start();

// chemin du site (laisser vide si à la racine) :
define('BASE_URL', '/test');

// nom du site :
define('SITE_NAME', 'TEST');

// les langues du site :
define('_LANGS', ['fr','en']);

// configuration filemanager :
define('SL_UPLOADS_PATH', './uploads');
define('SL_UPLOADS_URL', BASE_URL.'/uploads');

// configuration de l'utilisateur admin :
define('_ADMIN_NAME', 'test@example.com');
define('_ADMIN_PASS', '!admin!');

// config base de données :
$host = 'localhost';
$db   = 'test';
$user = 'root';
$pass = '';

// construction base de données :
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// stockage de la connexion dans la variable globale $dbing :
$dbing = $pdo;