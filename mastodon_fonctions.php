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


/**
 * Generer une URL courte pour les articles, si l'option est activee dans la configuration du plugin mastodon
 * @param int $id
 * @param string $entite
 * @param string $args
 * @param string $ancre
 * @param bool $public
 * @param null $type
 * @return string
 */
function generer_url_racourcie($id, $entite='article', $args='', $ancre='', $public=true, $type=null){
	include_spip('inc/filtres_mini');
	$config = unserialize($GLOBALS['meta']['mastodon']);

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

