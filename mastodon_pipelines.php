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