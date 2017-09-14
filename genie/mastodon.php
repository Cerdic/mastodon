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
 * Alerte sur les articles publies post-dates
 *
 * @param int $last
 * @return int
 */
function genie_mastodon_dist($last) {
	$cfg = @unserialize($GLOBALS['meta']['microblog']);
	// si le site utilise les articles postdates
	// et que l'on a configurer pour alerter a la publication uniquement
	// il faut surveiller les articles publies
	// $last est la date de la dernier occurence du cron, si vaut zero on ne fait rien
	if ($GLOBALS['meta']["post_dates"]=='non'
	and $cfg['evt_publierarticles']
	AND $cfg['evt_publierarticlesfutur']=='publication'
	AND $last){
		include_spip('inc/abstract_sql');
		$deja_annonces = explode(',',$GLOBALS['meta']['mastodon_annonces']);
		$deja_annonces = array_map('intval',$deja_annonces);

		$res = sql_select("id_article,statut","spip_articles",
			array(
				"statut='publie'",
				"date>".sql_quote(date("Y-m-d H:i:s",$last)),
				"date<=".sql_quote(date("Y-m-d H:i:s")),
				sql_in('id_article',$deja_annonces,"NOT")
			));
		include_spip('inc/mastodon_notifications');
		include_spip('inc/mastodon');
		while($row = sql_fetch($res)){
			$status = mastodon_annonce('instituerarticle',array('id_article'=>$row['id_article']));
			mastodon_envoyer_tweet($status,array('objet'=>'article','id_objet'=>$row['id_article']));
		}
		// raz des annonces deja faites
		include_spip('inc/meta');
		ecrire_meta('mastodon_annonces','0');
	}

	return 1;
}

/**
 * Ajouter la tache cron pour tweeter les articles post-dates, chaque heure
 * @param $taches_generales
 * @return mixed
 */
function mastodon_taches_generales_cron($taches_generales){
	if ($GLOBALS['meta']["post_dates"]=='non'
		AND	$cfg = @unserialize($GLOBALS['meta']['microblog'])
		and $cfg['evt_publierarticles']
		AND $cfg['evt_publierarticlesfutur']=='publication'){
		// surveiller toutes les heures les publications post-dates
		$taches_generales['mastodon'] = 3600;
	}
	return $taches_generales;
}

?>
