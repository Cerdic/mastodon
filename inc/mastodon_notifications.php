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
 * Buzzer les notifications
 * @param array $flux
 * @return array
 */
function mastodon_notifications($flux) {
  include_spip('inc/filtres_mini');
  include_spip('inc/texte');

	$status = null;
	$cfg = @unserialize($GLOBALS['meta']['mastodon']);
	switch($flux['args']['quoi']) {
		case 'forumposte':      // post forums
			if ($cfg['evt_forumposte']
			AND $id = intval($flux['args']['id'])) {
				// ne pas poster si le forum est valide et config forum valide activee
				if (sql_getfetsel("statut","spip_forum","id_forum=".intval($id))!="publie"
					OR !$cfg['evt_forumvalide']){
					$status = mastodon_annonce('forumposte',array('id_forum'=>$id));
					mastodon_envoyer_pouet($status,array('objet'=>'forum','id_objet'=>$id));
				}
			}
			break;
		case 'forumvalide':      // forum valide
			if ($cfg['evt_forumvalide']
			AND $id = intval($flux['args']['id'])) {
				$status = mastodon_annonce('forumvalide',array('id_forum'=>$id));
				mastodon_envoyer_pouet($status,array('objet'=>'forum','id_objet'=>$id));
			}
			break;

		case 'instituerarticle':    // publier | proposer articles
		if ($id = intval($flux['args']['id'])
			AND (
				// publier
				($cfg['evt_publierarticles']
					AND $flux['args']['options']['statut'] == 'publie'
					AND $flux['args']['options']['statut_ancien'] != 'publie'
					AND ($GLOBALS['meta']["post_dates"]=='oui'
						OR strtotime($flux['args']['options']['date'])<=time()
						OR $cfg['evt_publierarticlesfutur']!='publication'
					)
				)
			OR 
				// proposer
				($cfg['evt_proposerarticles']
				AND $flux['args']['options']['statut'] == 'prop'
				AND $flux['args']['options']['statut_ancien'] != 'publie'
				)
			)
		) {
			// si on utilise aussi le cron pour annoncer les articles post-dates
			// noter ceux qui sont deja annonces ici (pour eviter double annonce)
			if ($flux['args']['options']['statut'] == 'publie'
			  AND $GLOBALS['meta']["post_dates"]=='non'
				AND $cfg['evt_publierarticlesfutur']=='publication'
			){
				include_spip('inc/meta');
				ecrire_meta('mastodon_annonces',$GLOBALS['meta']['mastodon_annonces'].','.$id);
			}

			// en cas d'attente, on note la date du plus vieux, et on ajoute l'attente
			$attente = (isset($cfg['attente']) ? $cfg['attente'] : 1) * 60;
			$heure = time() + 60;
			if (isset($cfg['attente']) and ($attente = 60 * intval($cfg['attente'])) > 0) {
				$vieux = $GLOBALS['meta']['mastodon_vieux'];
				if ($vieux and $vieux>$heure-$attente) {
					$heure = $vieux + $attente;
				}
				ecrire_meta('mastodon_vieux', $heure);
			}

			$status = mastodon_annonce('instituerarticle',array('id_article'=>$id));
			mastodon_envoyer_pouet($status,array('objet'=>'article','id_objet'=>$id), $heure);
		}
		break;
	}

	return $flux;
}

function mastodon_annonce($quoi, $contexte){
	return trim(recuperer_fond("modeles/mastodon_$quoi", $contexte));
}

function mastodon_envoyer_pouet($status, $liens=array(), $heure = null){

	// un status vide ne provoque pas d'envoi
	if (!is_null($status) AND strlen($status)) {
		if ($heure === null) {
			$heure = time() + 60;
		}
		$id_job = job_queue_add('pouet',"Mastodon : $status",array($status),'inc/mastodon',true, $heure);
		if ($liens) {
			job_queue_link($id_job,$liens);
		}
	}
}
