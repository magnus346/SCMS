<?php
/**
 * Parser un fichier JSON, en tenant compte des constantes ( "{{CONST}}" ).
 *
 * @param  string  $path : le chemin du fichier.
 * @return array : la struct générée.
 */
function parseJSON($path) {
	$struct = json_decode(file_get_contents($path),true);
	array_walk_recursive($struct, function(&$e, $key) {
		if(is_string($e) && substr($e,0,2)=='{{' && substr($e,-2)=='}}')
			$e = constant(substr($e,2,strlen($e)-4));
	});
	return $struct;
}

/**
 * Traduire une chaine de caractères.
 *
 * @param  string  $str : la chaine à traduire.
 * @return string : la chaine traduite.
 */
function __($str) {
	global $translations, $current_lang;
	return isset($translations[$str]) ? (isset($translations[$str][$current_lang]) ? $translations[$str][$current_lang] : $translations[$str][array_keys($translations[$str])[0]]) : $str;
}

/**
 * Récupérer une url depuis le slug de la langue par défaut.
 *
 * @param  string  $slug : le slug dans langue par défaut.
 * @return string : l'url dans la langue courante.
 */
function slug_url($slug) {
	global $dbing, $current_lang;
	return BASE_URL.'/'.$current_lang.'/'.Slug::translate($dbing, 'commencer', _LANGS[0], $current_lang);
}

/**
 * Récupérer l'url de la racine dans la langue courante.
 *
 * @return string : l'url dans la langue courante.
 */
function home_url() {
	global $current_lang;
	return BASE_URL.'/'.$current_lang.'/';
}