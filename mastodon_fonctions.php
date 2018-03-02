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

/**
 * Autoriser le menu pouet : si configuration OK et si admin (ou autorisation specifique definie)
 * @return bool
 */
function autoriser_pouetter_menu_dist(){
	include_spip("inc/mastodon");
	if (!mastodon_verifier_config())
		return false;

	if (!autoriser('publier','pouet')) {
		return false;
	}
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
 * Affichage du formulaire de pouet
 *
 * @param array $flux
 * @return array
 */
function mastodon_afficher_complement_objet($flux){
	if ($flux['args']['type']=='article'
	  AND $id_article = $flux['args']['id']
	  AND include_spip('inc/config')
	  AND $cfg = lire_config('mastodon')
		AND ($cfg['evt_publierarticles'] OR $cfg['evt_proposerarticles'])
		AND $cfg['invite']
		){
		$flux['data'] .= recuperer_fond('prive/editer/pouet', array_merge($_GET, array('objet'=>'article','id_objet'=>$id_article)));
	}

	return $flux;
}


/**
 * Ajouter la tache cron pour tweeter les articles post-dates, chaque heure
 * @param $taches_generales
 * @return mixed
 */
function mastodon_taches_generales_cron($taches_generales){
	if ($GLOBALS['meta']["post_dates"]=='non'
		AND	$cfg = @unserialize($GLOBALS['meta']['mastodon'])
		and $cfg['evt_publierarticles']
		AND $cfg['evt_publierarticlesfutur']=='publication'){
		// surveiller toutes les heures les publications post-dates
		$taches_generales['mastodon'] = 3600;
	}
	return $taches_generales;
}

/**
 * Filtre shorthand pour utiliser mastodon_get_statuses dans un squelette
 * @param array $options
 * @return array
 */
function filtre_mastodon_get_statuses_dist($options) {
	include_spip('inc/mastodon');
	return mastodon_get_statuses($options);
}

/**
 * filtre pour determiner le mime des attachments
 * @param string $filename
 * @return string
 */
function mastodon_mime_type_fichier($filename) {
	$mime = "";
	if (preg_match(",[.](\w+)$,Uims", $filename, $m)) {
		$extension = strtolower($m[1]);
		if ($extension == 'jpeg') $extension = 'jpg';
		if ($type = sql_fetsel('titre, mime_type', 'spip_types_documents', 'extension = ' . sql_quote($extension))) {
			$mime = $type['mime_type'];
		}
	}
	return $mime;

}