<?php
class User
{	
	/**
	 * Connecter un utilisateur.
	 *
	 * @param  connexion  $db
	 * @param  string  $email : l'email de l'utilisateur à connecter.
	 * @param  string  $password : le mot de passe de l'utilisateur à connecter.
	 * @return bool : TRUE si connexion réussie, FALSE sinon.
	 */
	public static function login($db, $email, $password) {
		$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
		$stmt->execute(array($email));
		if($row=$stmt->fetch()) {
			if(password_verify($password, $row['password'])) {
				// generate and update token :
				$token = static::generateToken();
				$stmt = $db->prepare('UPDATE users SET token = ? WHERE id_user = ?');
				$stmt->execute(array($token, $row['id_user']));
				// clean current row and save in session :
				$row['token'] = $token;
				unset($row['password']);
				$_SESSION['user'] = $row;
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Déconnecter l'utilisateur en session.
	 *
	 * @return bool : TRUE.
	 */	
	public static function logout() {
		unset($_SESSION['user']);
		return TRUE;
	}
	
	/**
	 * Vérifier que l'utilisateur en session est connecté.
	 *
	 * @param  connexion  $db
	 * @return bool : TRUE si connexion active, FALSE sinon.
	 */	
	public static function checkAuth($db) {
		if(isset($_SESSION['user'])) {
			$stmt = $db->prepare('SELECT token FROM users WHERE id_user = ? LIMIT 1');
			$stmt->execute(array($_SESSION['user']['id_user']));
			if($row=$stmt->fetch())
				return ($row['token']===$_SESSION['user']['token']);
		}
		return FALSE;
	}

	/**
	 * Vérifier qu'un utilisateur existe pour de identifiants donnés.
	 *
	 * @param  connexion  $db
	 * @param  string  $email : l'email de l'utilisateur à vérifier.
	 * @param  string  $password : le mot de passe de l'utilisateur à vérifier.
	 * @return bool : TRUE si existe, FALSE sinon.
	 */	
	public static function checkCredentials($db, $email, $password) {
		$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
		$stmt->execute(array($email));
		if($row=$stmt->fetch()) {
			if(password_verify($password, $row['password'])) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Générer le jeton de connexion.
	 *
	 * @return void
	 */	
	public static function generateToken() {
		return bin2hex(random_bytes(32));
	}

	/**
	 * Créer (insérer dans `users`) un utilisateur.
	 *
	 * @param  connexion  $db
	 * @param  string  $name : le nom de l'utilisateur à créer.
	 * @param  string  $email : l'email de l'utilisateur à créer.
	 * @param  string  $password : le mot de passe de l'utilisateur à créer.
	 * @return bool : TRUE si création réussie, FALSE sinon.
	 */		
	public static function create($db, $name, $email, $password, $role) {
		$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
		$stmt->execute(array($email));
		if($row=$stmt->fetch()) {
			return FALSE;
		} else {
			// generate and update token :
			$token = static::generateToken();
			$stmt = $db->prepare('INSERT INTO users (name, email, password, role, token) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute(array($name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $token));
		}
		return TRUE;
	}
}