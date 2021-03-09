<?php
class Order
{	
	/**
	 * After insert : mettre à jours la colonne `ordre` après l'insertion de l'entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $table : la table.
	 * @param  string  $primary : la colonne PRIMARY KEY de $table.
	 * @param  int  $inserted_id : l'id de l'entité à modifier.
	 * @return void.
	 */
	public static function insert($db, $table, $primary, $inserted_id) {
		$sth = $db->prepare("SELECT MAX(`order`) AS m FROM ".$table."");
		$sth->execute();
		$m = intval($sth->fetchColumn())+1;	
		$stmt=$db->prepare("UPDATE ".$table." SET `order` = ? WHERE ".$primary." = ?");
		$stmt->execute(array($m, $inserted_id));		
	}

	/**
	 * Update : modifier la colonne `ordre` et réarranger les autres entités du même type en fonction.
	 *
	 * @param  connexion  $db
	 * @param  string  $table : la table.
	 * @param  string  $primary : la colonne PRIMARY KEY de $table.
	 * @param  int  $id : l'id de l'entité à modifier.
	 * @param  bool  $uo : TRUE si l'ordre doit etre incrémenté, FALSE sinon.
	 * @return void.
	 */
	public static function update($db, $table, $primary, $id, $up) {
		$sth = $db->prepare("SELECT `order` AS m FROM ".$table." WHERE ".$primary." = ?");
		$sth->execute(array($_POST['set_order']['id']));
		$m = intval($sth->fetchColumn());
		if($up)
			$stmt=$db->prepare("UPDATE ".$table." SET `order` = ".($m)." WHERE `order` = ".($m+1));
		else
			$stmt=$db->prepare("UPDATE ".$table." SET `order` = ".($m)." WHERE `order` = ".($m-1));
		$stmt->execute();
		if($up)
			$stmt=$db->prepare("UPDATE ".$table." SET `order` = ".($m+1)." WHERE ".$primary." = ?");
		else
			$stmt=$db->prepare("UPDATE ".$table." SET `order` = ".($m-1)." WHERE ".$primary." = ?");
		$stmt->execute(array($id));
	}
	
	/**
	 * Before delete : réarranger les autres entités du même type en prévision de la suppression de l'entité.
	 *
	 * @param  connexion  $db
	 * @param  string  $table : la table.
	 * @param  string  $primary : la colonne PRIMARY KEY de $table.
	 * @param  int  $id : l'id de l'entité à modifier.
	 * @return void.
	 */
	public static function delete($db, $table, $primary, $id) {
		$sth = $db->prepare("SELECT `order` AS m FROM ".$table." WHERE ".$primary." = ?");
		$sth->execute(array($id));
		$m = intval($sth->fetchColumn());
		$stmt=$db->prepare("UPDATE ".$table." SET `order` = `order` - 1 WHERE `order` > ".$m);
		$stmt->execute();
	}	

	/**
	 * Récupérer le code HTML des boutons pour changer l'ordre.
	 *
	 * @param  connexion  $db
	 * @param  string  $table : la table.
	 * @param  array  $row : l'entité.
	 * @param  array  $ss_struct : la struct de cette table.
	 * @param  int  $count : l'ordre de cette entité dansl'affichage.
	 * @param  string  $url : l'url courante.
	 * @return string : le cdde HTML généré.
	 */	
	public static function getForm($db, $table, $row, $ss_struct, $count, $url) {
		$sth = $db->prepare("SELECT MAX(`order`) AS m FROM ".$table."");
		$sth->execute();
		$max_order = intval($sth->fetchColumn());	
		$html = '
			<td width="1%" style="white-space:nowrap;">
				'.(($count>1) ? 
				'<form style="margin:0;display:inline;" method="post" action="'.BASE_URL.'/'.$url.'"><input type="hidden" name="set_order[id]" value="'.$row[$ss_struct['primary']].'"/><input type="hidden" name="set_order[up]" value="0"/><button class="btn" style="padding: 0.375rem 0rem"><span class="material-icons" style="color:#000">expand_less</span></button></form>
				' : '').(($count<$max_order) ? 
				'<form style="margin:0;display:inline;" method="post" action="'.BASE_URL.'/'.$url.'"><input type="hidden" name="set_order[id]" value="'.$row[$ss_struct['primary']].'"/><input type="hidden" name="set_order[up]" value="1"/><button class="btn" style="padding: 0.375rem 0rem"><span class="material-icons" style="color:#000">expand_more</span></button></form>
				' : '').'
			</td>	
		';
		return $html;
	}
}