<?php
class Slug
{	
	/**
	 * Insérer un slug pour une entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $sluggable_table : la table de l'entité pour laquelle créer un slug.
	 * @param  string  $sluggable_id : l'ID de l'entité pour laquelle créer un slug.
	 * @param  array  $post : les données de envoyées par le formulaire.
	 * @return void.
	 */
	public static function insert($db, $sluggable_table, $sluggable_id, $post=[]) {
		$data_slug = [];
		foreach(_LANGS as $l) {
			$data_slug['slug_'.$l] = isset($post['slug_'.$l]) ? trim($post['slug_'.$l]) : NULL;
		}
		$data_slug['sluggable_table'] = $sluggable_table;
		$data_slug['sluggable_id'] = $sluggable_id;
		$stmt=$db->prepare("INSERT INTO slugs (".implode(',',array_keys($data_slug)).") VALUES (".implode(',',array_map(function($e) {return '?';}, $data_slug)).")");
		$stmt->execute(array_values($data_slug));	
	}	

	/**
	 * Mettre à jour le slug d'une une entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $sluggable_table : la table de l'entité pour laquelle mettre à jour le slug.
	 * @param  string  $sluggable_id : l'ID de l'entité pour laquelle mettre à jours le slug.
	 * @param  array  $post : les données de envoyées par le formulaire.
	 * @return void.
	 */	
	public static function update($db, $sluggable_table, $sluggable_id, $post=[]) {
		$data_slug = [];
		$sets_slug = [];
		foreach(_LANGS as $l) {
			$sets_slug[] = 'slug_'.$l.' = ?';
			$data_slug[] = isset($post['slug_'.$l]) ? trim($post['slug_'.$l]) : NULL;
		}
		$data_slug[] = $sluggable_id;
		$stmt=$db->prepare("UPDATE slugs SET ".implode(',',$sets_slug)." WHERE sluggable_table = '".$sluggable_table."' AND sluggable_id = ?");
		$stmt->execute(array_values($data_slug));
	}

	/**
	 * Supprimer le slug d'une une entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $sluggable_table : la table de l'entité pour laquelle supprimer le slug.
	 * @param  string  $sluggable_id : l'ID de l'entité pour laquelle supprimer le slug.
	 * @return void.
	 */	
	public static function delete($db, $sluggable_table, $sluggable_id) {
		$stmt=$db->prepare("DELETE FROM slugs WHERE sluggable_table = '".$sluggable_table."' AND sluggable_id = ?");
		$stmt->execute(array($sluggable_id));			
	}

	/**
	 * Traduire - obtenir le slug dans une autre langue
	 *
	 * @param  connexion  $db
	 * @param  string  $slug : la valeur du slug à traduire.
	 * @param  string  $from : la langue d'origne.
	 * @param  string  $to : la langue vers laquelle traduire.
	 * @return void.
	 */		
	public static function translate($db, $slug, $from, $to) {
		$stmt = $db->prepare("SELECT * FROM slugs WHERE slug_".$from." = ? LIMIT 1");
		if($stmt->execute(array($slug))) {
			if($row = $stmt->fetch()) {
				return $row['slug_'.$to];
			}
		}
		return BASE_URL.'/'.$to.'/';
	}

	/**
	 * Récupérer le slug d'une une entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $sluggable_table : la table de l'entité pour laquelle récupérer le slug.
	 * @param  string  $sluggable_id : l'ID de l'entité pour laquelle récupérer le slug.
	 * @return void.
	 */		
	public static function get($db, $sluggable_table, $sluggable_id) {
		$stmt = $db->prepare("SELECT * FROM slugs WHERE sluggable_table = '".$sluggable_table."' AND sluggable_id = ? LIMIT 1");
		if($stmt->execute(array($sluggable_id))) {
			if($row = $stmt->fetch()) {
				return $row;
			}
		}
		return false;
	}

	/**
	 * Récupérer tous les slugs d'une table.
	 *
	 * @param  connexion  $db
	 * @param  string  $sluggable_table : la table pour laquelle récupérer les slugs.
	 * @return void.
	 */			
	public static function getAllForTable($db, $sluggable_table) {
		global $struct;
		$ss_struct = $struct[$sluggable_table];
		$res=[];
		$stmt = $db->prepare("SELECT * FROM slugs S INNER JOIN ".$sluggable_table." T ON T.".$ss_struct['primary']." = S.sluggable_id WHERE sluggable_table = ? ".(isset($ss_struct['column']['order']) ? 'ORDER BY `order` ASC' : ''));
		if($stmt->execute(array($sluggable_table))) {
			while($row = $stmt->fetch()) {
				$res[] = $row;
			}
		}
		return $res;
	}

	/**
	 * Récupérer le code HTML du formulaire des slugs pour une une entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $sluggable_table : la table de l'entité pour laquelle récupérer le HTML.
	 * @param  string  $sluggable_id : l'ID de l'entité pour laquelle récupérer le HTML.
	 * @return void.
	 */			
	public static function getForm($db, $sluggable_table, $sluggable_id) {
		$ss_slug = static::get($db, $sluggable_table, $sluggable_id);
		$html = '
		<div class="scms scms-group card">
		<div class="card-body">
			<h5 class="card-title">Slugs :</h5>
			<div class="scms-group-labels">
		';
		foreach(_LANGS as $l) {
			$html .= '<label><img src="'.BASE_URL.'/src/images/flag_'.$l.'" height="25px"/></label>';
		}
		$html .='
			</div>
			<div class="scms-group-inputs">
		';
		foreach(_LANGS as $l) {
			$html .='
			<div class="scms-input">
				<span class="scms-slug">'.BASE_URL.'/'.$l.'/<input '.(($ss_slug['slug_'.$l]===NULL) ? 'disabled placeholder="{privé}"' : '').' type="text" name="slug_'.$l.'" value="'.(isset($ss_slug['slug_'.$l]) ? $ss_slug['slug_'.$l] : '').'"/></span>
			';
			if(isset($ss_slug['slug_'.$l])) {
				$html .= '
				<a style="margin-left:10px;font-size:0.7em;vertical-align:top;" class="btn btn-info badge-info badge-pill" target="_scms" href="'.BASE_URL.'/'.$l.'/'.$ss_slug['slug_'.$l].'">
					&nbsp;<span class="material-icons">remove_red_eye</span>&nbsp;
				</a>
				';
			}
			$html .= '
			</div>
			';
		}
		$html .= '
			</div>
		</div>
		</div>
		';
		return $html;
	}
}