<?php
class Table
{
	/** Nom de la table : */	
	public $name;
	/** Colonnes de la table : */	
	public $columns = [];
	/** Indexes de la table : */	
	public $indexes = [];
	
	/** Requetes SQL générées : */	
	protected $sql = [];
	/** Connexion : */	
	protected $db;
	
	/**
	 * Construct : initialiser la connexion.
	 *
	 * @return void.
	 */
	public function __construct($db) {
		$this->db = $db;
	}

	/**
	 * Ajouter une colonne à la définition de la table.
	 *
	 * @param  string  $name : nom de la colonne.
	 * @param  string  $def : définition SQL de la colonne.
	 * @return void.
	 */
	public function column($name, $def) {
		$this->columns[$name] = static::colSanitize($def);
	}

	/**
	 * Ajouter un index à la définition de la table.
	 *
	 * @param  string  $name : nom de la colonne à indexer.
	 * @return void.
	 */	
	public function index($name) {
		$this->indexes[$name] = 'KEY';
	}

	/**
	 * Ajouter un index de type UNIQUE à la définition de la table.
	 *
	 * @param  string  $name : nom de la colonne à indexer.
	 * @return void.
	 */		
	public function unique($name) {
		$this->indexes[$name] = 'UNIQUE KEY';
	}

	/**
	 * Ajouter un index de type PRIMARY à la définition de la table.
	 *
	 * @param  string  $name : nom de la colonne à indexer.
	 * @return void.
	 */		
	public function primary($name) {
		$this->indexes[$name] = 'PRIMARY KEY';
	}

	/**
	 * Ajouter un des champs usuels de type date à la définition de la table.
	 *
	 * @param  string  $def : définition SQL des colonne.
	 * @return void.
	 */		
	public function timestamps($def='DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP') {
		$this->columns['created_at'] = static::colSanitize($def);
		$this->columns['updated_at'] = static::colSanitize($def);
	}	

	/**
	 * Définir une variation sur une colonne.
	 *
	 * @param  string  $name : nom de la colonne à varier.
	 * @param  string  $variations : liste des variations à effectuer.
	 * @return void.
	 */	
	public function variate($name, $variations) {
		if(isset($this->columns[$name])) {
			$def = $this->columns[$name];
			$index = array_search($name, array_keys($this->columns));
			$insert = array();
			foreach($variations as $v) {
				$insert[$name.'_'.$v] = $def;
			}
			$this->columns = array_slice($this->columns, 0, $index, true) + $insert + array_slice($this->columns, $index+1, count($this->columns) - 1, true);
		}
	}

	/**
	 * Définir une variation sur un index.
	 *
	 * @param  string  $name : nom de l'index à varier.
	 * @param  string  $variations : liste des variations à effectuer.
	 * @return void.
	 */		
	public function variateIndex($name, $variations) {
		if(isset($this->indexes[$name])) {
			$def = $this->indexes[$name];
			$index = array_search($name, array_keys($this->indexes));
			$insert = array();
			foreach($variations as $v) {
				$insert[$name.'_'.$v] = $def;
			}
			$this->indexes = array_slice($this->indexes, 0, $index, true) + $insert + array_slice($this->indexes, $index+1, count($this->indexes) - 1, true);
		}
	}

	/**
	 * Définir une variation sur un index de type UNIQUE.
	 *
	 * @param  string  $name : nom de l'index à varier.
	 * @param  string  $variations : liste des variations à effectuer.
	 * @return void.
	 */			
	public function variateUnique($name, $variations) {
		if(isset($this->indexes[$name])) {
			$def = $this->indexes[$name];
			$index = array_search($name, array_keys($this->indexes));
			$insert = array();
			foreach($variations as $v) {
				$insert[$name.'_'.$v] = $def;
			}
			$this->indexes = array_slice($this->indexes, 0, $index, true) + $insert + array_slice($this->indexes, $index+1, count($this->indexes) - 1, true);
		}
	}

	/**
	 * Générer les requetes de migration pour la table.
	 *
	 * @return void.
	 */		
	public function migrate() {
		$this->sql = [];
		if(!$this->exists()) {
			$this->create();
		} else {
			list($columns, $indexes) = $this->schema();
			foreach($indexes as $name=>$type) {
				if(!isset($this->indexes[$name]))
					$this->dropIndex($name, $type);
			}
			foreach($this->columns as $name=>$def) {
				if(isset($columns[$name])) {
					if(!static::defEquals($def, $columns[$name]))
						$this->alterColumn($name, $def);
					unset($columns[$name]);
				} else {
					$this->addColumn($name, $def);
				}
			}
			foreach($columns as $name=>$def) {
				$this->dropColumn($name);
			}
			foreach($this->indexes as $name=>$type) {
				if(!isset($indexes[$name]))
					$this->addIndex($name, $type);
			}
		}
		$sql = $this->sql;
		$this->sql = [];
		return $sql;
	}

	/**
	 * Récupérer le schéma pour la table depuis la base de données.
	 *
	 * @return void.
	 */			
	public function schema() {
		if(!$this->exists())
			return FALSE;
		$result = $this->db()->query('SHOW CREATE TABLE '.$this->name);
		$result = $result->fetch()['Create Table'];
		$columns = array();
		$indexes = array();
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $result) as $line){
			$line = trim(preg_replace('/\s+/', ' ', $line));
			if(substr($line, 0, 12)!='CREATE TABLE' && substr($line, 0, 1)!=')') {
				if(substr($line, 0, 1)=='`') {
					$line = str_ireplace('`', '', $line);
					$name = explode(' ', $line)[0];
					$def = substr($line, mb_strlen($name)+1);
					$columns[$name] = strtoupper(rtrim($def,','));
				} else {
					$type = substr($line, 0, min(strpos($line, '('), strpos($line, '`'))-1);
					$name = substr($line, mb_strlen($type)+1);
					$name = preg_match('#\((.*?)\)#', $name, $match);
					$name = trim(str_ireplace('`', '', $match[1]));
					$indexes[$name] = strtoupper(rtrim($type,','));				
				}
			}	
		} 		
		return [$columns, $indexes];
	}

	/**
	 * Remplir la définition avec les données d'une struct.
	 *
	 * @param  array  $a : la struct.
	 * @return void.
	 */		
	public function fill($a) {
		if(isset($a['column'])) {
			foreach($a['column'] as $name=>$e)
				$this->column($name, $e);
		}
		if(isset($a['index'])) {
			foreach($a['index'] as $e)
				$this->index($e);
		}
		if(isset($a['unique'])) {
			foreach($a['unique'] as $e)
				$this->unique($e);
		}
		if(isset($a['primary'])) {
			$this->primary($a['primary']);
		}
		if(isset($a['timestamps']) && $a['timestamps']) {
			$this->timestamps();
		}
		if(isset($a['variate'])) {
			foreach($a['variate'] as $name=>$e) {
				$this->variate($name, $e);
			}
		}
		if(isset($a['variateIndex'])) {
			foreach($a['variateIndex'] as $name=>$e) {
				$this->variateIndex($name, $e);
			}
		}
		if(isset($a['variateUnique'])) {
			foreach($a['variateUnique'] as $name=>$e) {
				$this->variateUnique($name, $e);
			}
		}
	}

	/**
	 * Récupérer la colonne PRIMARY.
	 *
	 * @return bool : la colonne PRIMARY.
	 */		
	public function getPrimaryKey() {
		foreach($this->indexes as $name=>$type) {
			if($type=='PRIMARY KEY')
				return $name;
		}
		return false;
	}
	
	/* ---------------------------------------------
	// Core methods (protected) :
	--------------------------------------------- */

	/**
	 * Récupérer l'instance de connexion à la base de données.
	 *
	 * @return connexion : la connexion à la base de données.
	 */		
	protected function db() {
		return $this->db;
	}

	/**
	 * Récupérer l'instance de connexion à la base de données.
	 *
	 * @return connexion : la connexion à la base de données.
	 */		
	protected function create() {
		$sql = 'CREATE TABLE IF NOT EXISTS '.$this->name.' (';
		foreach($this->columns as $name=>$def) {
			$sql.= ' `'.$name.'` '.$def.',';
		}
		foreach($this->indexes as $name=>$type) {
			$sql.= ' '.$type.' (`'.$name.'`),';
		}
		$sql = substr($sql, 0, -1).' ) ENGINE=InnoDB';
		$this->sql[] = $sql;
		return true;
	}

	/**
	 * Ajouter DROP TABLE à la liste des requetes SQL.
	 *
	 * @return void.
	 */		
	protected function drop() {
		$this->sql[] = 'DROP TABLE IF EXISTS '.$this->name;
	}

	/**
	 * Vérifier que la table existe dans la base de données.
	 *
	 * @return bool : l'exitence de la table.
	 */		
	protected function exists() {
		try {
			$result = $this->db()->query('SELECT 1 FROM '.$this->name.' LIMIT 1');
		} catch (Exception $e) {
			return FALSE;
		}
		return $result !== FALSE;
	}

	/**
	 * Ajouter ADD COLUMN à la liste des requetes SQL.
	 *
	 * @param  string  $name : nom de la colonne.
	 * @param  string  $def : définition de la colonne.
	 * @return void.
	 */		
	protected function addColumn($name, $def) {
		//var_dump(['ADD', $name, $def]);
		$this->sql[] = 'ALTER TABLE '.$this->name.' ADD COLUMN `'.$name.'` '.$def;	
	}

	/**
	 * Ajouter MODIFY COLUMN à la liste des requetes SQL.
	 *
	 * @param  string  $name : nom de la colonne.
	 * @param  string  $def : définition de la colonne.
	 * @return void.
	 */			
	protected function alterColumn($name, $def) {
		//var_dump(['ALTER', $name, $def]);
		$this->sql[] = 'ALTER TABLE '.$this->name.' MODIFY `'.$name.'` '.$def;	
	}

	/**
	 * Ajouter DROP COLUMN à la liste des requetes SQL.
	 *
	 * @param  string  $name : nom de la colonne.
	 * @return void.
	 */			
	protected function dropColumn($name) {
		//var_dump(['DROP', $name]);
		$this->sql[] = 'ALTER TABLE '.$this->name.' DROP COLUMN `'.$name.'`';	
	}

	/**
	 * Ajouter ADD INDEX à la liste des requetes SQL.
	 *
	 * @param  string  $name : nom de la colonne.
	 * @param  string  $type : type d'index (primary, index, unique...).
	 * @return void.
	 */			
	protected function addIndex($name, $type) {
		//var_dump(['ADD', $name, $type]);
		$this->sql[] = 'ALTER TABLE '.$this->name.' ADD '.$type.' (`'.$name.'`)';	
	}

	/**
	 * Ajouter DROP INDEX à la liste des requetes SQL.
	 *
	 * @param  string  $name : nom de la colonne.
	 * @param  string  $type : type d'index (primary, index, unique...).
	 * @return void.
	 */		
	protected function dropIndex($name, $type) {
		$this->sql[] = 'DROP INDEX '.($type=='PRIMARY KEY' ? '`PRIMARY`' : '`'.$name.'`').' ON '.$this->name;	
	}
	
	/* ---------------------------------------------
	// Static helper methods :
	--------------------------------------------- */

	/**
	 * Récupérer les définitions des colonnes de la table, depuis la base de données.
	 *
	 * @param  connexion  $db
	 * @param  string  $name : le nom de la table.
	 * @return array : définitions des colonnes la table.
	 */		
	public static function getSchemaFor($db, $name) {
		$t = new Table($db);
		$t->name = $name;
		return $t->schema()[0];		
	}
	
	/**
	 * Comparer deux définitions de tables.
	 *
	 * @param  string  $a : la définition A.
	 * @param  string  $b : le définition B.
	 * @return bool : TRUE si les définitions sont éguales, FALSE sinon.
	 */		
	protected static function defEquals(&$a, &$b) {
		preg_match('#\((.*?)\)#', $a, $a_has_length);
		preg_match('#\((.*?)\)#', $b, $b_has_length);
		if($a_has_length && !$b_has_length)
			$a = str_replace($a_has_length[0], '', $a);
		elseif($b_has_length && !$a_has_length)
			$b = str_replace($b_has_length[0], '', $b);
		$a = trim(preg_replace('/\s+/', ' ', static::defSanitize($a)));
		$b = trim(preg_replace('/\s+/', ' ', static::defSanitize($b)));
		//var_dump([$a, $b]);
		return $a==$b;
	}

	/**
	 * Nettoyer une définition de table, en la minimalisant pour des comparaisons plus faciles.
	 *
	 * @param  string  $def : la définition de la table.
	 * @return string : la définition nettoyée.
	 */			
	protected static function defSanitize($def) {
		$search = [
			'/\sNOT NULL AUTO_INCREMENT(\s|$)/',
			'/\sDEFAULT NULL(\s|$)/'
		];
		
		$replace = [
			' AUTO_INCREMENT$1',
			' $1',
		];
		
		return preg_replace($search, $replace, $def);
	}

	/**
	 * Nettoyer une définition de colonne, en la minimalisant pour des comparaisons plus faciles.
	 *
	 * @param  string  $def : la définition de la colonne.
	 * @return string : la définition nettoyée.
	 */		
	protected static function colSanitize($def) {
		return preg_replace('/(VARCHAR)(\s|$)/', 'VARCHAR(255)$2', preg_replace('/([A-Z]+)\s*\(/', '$1(', strtoupper($def)));
	}
	
	/**
	 * Construire le tableau des données d'une table, depuis les valeurs POST d'un formulaire.
	 *
	 * @param  connexion  $db
	 * @param  string  $name : le nom de la table.
	 * @param  array  $post : les valeurs POST.
	 * @param  array  $handlers : les fonctions de vérification à appliquer.
	 * @return array : le tableau de données construit.
	 */			
	public static function validateInput($db, $name, $post=[], $handlers=[]) {
		$data = [];
		$schema = static::getSchemaFor($db, $name);
		foreach($schema as $k=>$v) {
			if(strpos($v, 'AUTO_INCREMENT')!==false)
				continue;
			if(isset($_POST[$k]))
				$data[$k] = $_POST[$k];
		}
		foreach($data as $k=>$v) {
			if(isset($handlers[$k]))
				$data[$k]=$handlers[$k]($v);
		}
		return $data;
	}
}