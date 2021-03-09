<?php
class Field
{	
	/**
	 * Récupérer les types des champs de formulaire depuis la struct.
	 *
	 * @param  array  $struct
	 * @return array : la liste des types pour chaque champ de la struct.
	 */
	public static function getFieldsFromStruct($struct) {
		$fields = [];
		foreach($struct['column'] as $k=>$v) {
			// ne pas integre la clé primaire :
			if($k===$struct['primary'])
				continue;
			// ne pas intégrer la created_at :
			if($k==='created_at')
				continue;	
			// ne pas intégrer la updated_at :
			if($k==='updated_at')
				continue;
			// ne pas intégrer l'ordre :
			if($k==='order')
				continue;
			// ne pas intégrer ceux qui sont définis dans la struct comme étant à exclure :
			if(isset($struct['exclude']) && in_array($k, $struct['exclude']))
				continue;
			$fields[$k] = static::getType($k, $v, isset($struct['forceFields']) ? $struct['forceFields'] : []);
		}
		// insertion des variations :
		if(isset($struct['variate'])) {
			foreach($struct['variate'] as $k=>$v) {
				if(isset($struct['exclude']) && in_array($k, $struct['exclude']))
					continue;
				$c = $fields[$k];
				$fields[$k] = array();
				foreach($v as $e)
					$fields[$k][$e] = $c;
			}
		}	
		return $fields;
	}

	/**
	 * Calculer le type d'un champ en fonction de son type en base de données.
	 *
	 * @param  string  $name : nom du champ.
	 * @param  string  $def : définition du champ en base de données.
	 * @param  array  $force : liste des champ pour lesquels on doit "forcer" un certain type.
	 * @return string : le type.
	 */
	public static function getType($name, $def, $force) {
		if(isset($force[$name]))
			return $force[$name];
		if(strpos($def, 'DATE')!==false)
			return 'date';
		if(strpos($def, 'TIME')!==false)
			return 'date';
		if(strpos($def, 'TEXT')!==false)
			return 'textarea';
		if(strpos($def, 'INT')!==false)
			return 'number';
		if(strpos($def, 'ENUM')!==false) {
			preg_match('#\((.*?)\)#', $def, $match);
			if(isset($match[1]))
				return 'enum|'.$match[1];
		}
		return 'text';
	}
	
	/**
	 * Récupérer le code HTML d'un champ
	 *
	 * @param  string  $name : nom du champ.
	 * @param  string  $field : type du champ (text, textarea ...).
	 * @param  array  $value : la valeur du champ.
	 * @return string : le cdde HTML généré.
	 */
	public static function getHtml($name, $field, $value) {
		$compos = explode('|',$field);
		$field = $compos[0];
		unset($compos[0]);
		$params = array($name, $value);
		if($compos)
			foreach($compos as $c)
				$params[] = $c;
		if(method_exists(static::class, 'field_'.$field))
			return call_user_func_array(array(static::class, 'field_'.$field), $params);
		return static::field_text($name, $value);
	}
	
	/**
	 * Récupérer le code HTML d'un champ de type TEXT
	 *
	 * @param  string  $name : nom du champ.
	 * @param  string  $value : valeur du champ.
	 * @return string : le cdde HTML généré.
	 */	
	public static function field_text($name, $value) {
		return '<input class="form-control" type="text" name="'.$name.'" value="'.$value.'"/>';
	}
	/**
	 * Récupérer le code HTML d'un champ de type TEXTAREA
	 */		
	public static function field_textarea($name, $value) {
		return '<textarea name="'.$name.'">'.$value.'</textarea>';
	}
	/**
	 * Récupérer le code HTML d'un champ de type PASSWORD
	 */	
	public static function field_password($name, $value) {
		return '<input class="form-control" type="password" name="'.$name.'" value="'.$value.'"/>';
	}
	/**
	 * Récupérer le code HTML d'un champ de type TEL
	 */		
	public static function field_tel($name, $value) {
		return '<input class="form-control" type="tel" name="'.$name.'" value="'.$value.'"/>';
	}
	/**
	 * Récupérer le code HTML d'un champ de type IMG
	 */		
	public static function field_img($name, $value) {
		return '<input type="filepicker" name="'.$name.'" value="'.$value.'" accept="image/jpeg" placeholder="Image en-tete" thumbnail/>';
	}
	/**
	 * Récupérer le code HTML d'un champ de type ENUM
	 */		
	public static function field_enum($name, $value, $list) {
		$list = eval("return array(".$list.");");
		$html = '<select name="'.$name.'">';
		foreach($list as $v)
			$html .= '<option value="'.$v.'" '.($v===$value ? 'selected' : '').'>'.ucfirst(str_ireplace('_', ' ', $v)).'</option>';
		$html .= '</select>';
		return $html;
	}
}