<?php
/*
 * Plugin spip|mastodon
 * (c) 2009-2013
 *
 * envoyer et lire des messages de Mastodon
 * distribue sous licence GNU/LGPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function autoriser_pouetter_menu_dist(){
	include_spip("inc/mastodon");
	if (!mastodon_verifier_config())
		return false;
	return true;
}

/**
 * Retrouver le screen name complet a partir des users infos de l'API
 * @param $user_infos
 * @return string
 */
function mastodon_user_full_screen_name($user_infos) {
	$screen_name = '@' . $user_infos['username'];
	$host = parse_url($user_infos['url'], PHP_URL_HOST);
	$screen_name .= '@' . $host;
	return $screen_name;
}

function generer_url_microblog($id, $entite='article', $args='', $ancre='', $public=true, $type=null){
	include_spip('inc/filtres_mini');
	$config = unserialize($GLOBALS['meta']['microblog']);

	if (!$public
	 OR $entite!=='article'
	 OR !$config['short_url'])
		return url_absolue(generer_url_entite($id, $entite, $args, $ancre, $public, $type));
	else
		return $GLOBALS['meta']['adresse_site'].'/'.$id;
}


/**
 * Fonction d'utilisation simple de l'API mastodon oAuth
 *
 * @param $command string : la commande à passer
 * @param $type string : le type de commande (get/post/delete)
 * @param $params array : les paramètres dans un array de la commande
 * @param $retour string : le retour souhaité par défaut cela renverra la chaine
 * ou l'array retourné par la commande. Sinon on peut utiliser les valeurs http_code,http_info,url
 * @param array $tokens
 * @return bool|string|array
 */
function microblog_mastodon_api($command,$type='get',$params=array(),$retour='',$tokens=null){
	$options = $tokens;
	if ($retour)
		$options['return_type'] = $retour;
	include_spip("inc/mastodon");
	return mastodon_api_call($command, $type, $params, $options);
}


/**
 * Pour utiliser |mastodon_api_call dans un squelette
 * @use mastodon_api_call
 *
 * @param string $command
 * @param string $type
 * @param array $params
 * @param array $options
 * @return array|bool|string
 */
function filtre_mastodon_api_call_dist($command,$type='get',$params=array(),$options=null){
	include_spip("inc/mastodon");
	return mastodon_api_call($command, $type, $params, $options);
}


/**
 * Afficher un pouet avec les liens (hashtag, mentions, urls)
 * @param string $texte
 * @param bool|false $is_backend
 * @return string
 */
function mastodon_joli_pouet($texte,$is_backend=false){
	defined('_EXTRAIRE_TW_LIENS') || define('_EXTRAIRE_TW_LIENS', ',' . '\[[^\[\]]*(?:<-|->).*?\]' . '|<a\b.*?</a\b' . '|<\w.*?>' . '|((?:https?:/|www\.)[^"\'\s\[\]\}\)<>]*)' .',imsS');

	// si c'est un pouet qui vient du flux RSS il commence par le mastodon user qu'on enleve
	if ($is_backend){
		$texte = preg_replace(",^\w+:\s,","",$texte);
	}

	// les autoliens
	$texte = preg_replace_callback(_EXTRAIRE_TW_LIENS,"mastodon_filtre_autolinks",$texte);
	// les liens vers les compte
	if (strpos($texte,"@")!==false){
		$texte = preg_replace_callback(",(@\w+@\w+\.\w+)\b(?!…),u","mastodon_filtre_user_link",$texte);
	}
	if (strpos($texte,"#")!==false){
		$texte = preg_replace_callback(",(#\w+)\b(?!…),u", "mastodon_filtre_hash_link", $texte);
	}

	return $texte;
}

/**
 * Remplacer les mentions par un lien vers la page de l'user mentionne
 * @param $m
 * @return mixed|string
 */
function mastodon_filtre_user_link($m){
	$user = reset($m);
	$user = ltrim($user,"@");
	$user = explode('@', $user);
	$host = end($user);
	$user = reset($user);
	$user = "<a href=\"https://$host/@$user\" target='_blank'>@$user</a>";
	return $user;
}

/**
 * Remplacer les hashtags par un lien vers la page hashtag chez mastodon
 * @param $m
 * @return mixed|string
 */
function mastodon_filtre_hash_link($m){
	$hash = reset($m);
	$hash = ltrim($hash,"#");
	if (is_numeric($hash))
		return $m[0];
	$hash = "<a href=\"https://mastodon.com/hashtag/$hash?src=hash\" target='_blank'>#$hash</a>";
	return $hash;
}

/**
 * Remplacer l'URL short du pouet par un lien vers l'URL short mais en affichant une version lisible
 * de la vrai URL
 *
 * @param $m
 * @return string
 */
function mastodon_filtre_autolinks($m){

	$link = $m[0];
	if (strpos($link,"&#8230;")!==false OR strpos($link,"…")!==false)
		return $link;

	$dir = sous_repertoire(_DIR_CACHE,"mastodon-links");
	$fichier_cache = $dir.md5($link)."txt";
	lire_fichier($fichier_cache,$target);
	if (!$target OR _request('var_mode')=='recalcul'){
		$target = $link;
		include_spip("inc/distant");
		for ($i = 0; $i<10; $i++){
			$res = recuperer_lapage($target, false, "HEAD", 0);
			if (!$res) return $link;
			if (is_array($res)){
				break;
			}
			$target = $res;
		}
		// ici $target est la vraie url finale, $link est l'url raccourcie
		$target = ltrim(protocole_implicite($target), "//");
		if (strncmp($target, "www.", 4)==0)
			$target = substr($target, 4);

		if (strlen($target)>40){
			$target = explode("/", $target);
			$first = array_shift($target);
			$target = implode("/", $target);
			$target = $first . "/" . couper(substr($target, 0, -10), 10, "") . "…" . substr($target, -10);
		}

		// on cache
		ecrire_fichier($fichier_cache, $target);
	}

	return "<a href=\"$link\" target='_blank'>$target</a>";
}
