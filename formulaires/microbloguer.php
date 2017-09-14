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
 * Fonction de chargement des valeurs par defaut des champs du formulaire
 */
function formulaires_microbloguer_charger_dist(){
	$valeurs =
		array(
			'status' => '',
		);
	include_spip("inc/mastodon");
	if (!mastodon_verifier_config()){
		$valeurs['editable'] = false;
		$valeurs['message_erreur'] = _T('mastodon:erreur_config_pour_tweeter');
	}
	return $valeurs;
}

/**
 * Fonction de vérification du formulaire avant traitement
 * 
 * Vérifie la présence d'un statut depuis le champs adéquat
 * Vérifie que la longueur du statut n'excède pas la longueur maximale
 */
function formulaires_microbloguer_verifier_dist(){
	include_spip('inc/charsets');
	$erreurs = array();
	if (!$status = _request('status')){
		$erreurs['status'] = _T('info_obligatoire');
	}
	elseif (spip_strlen($status)>140){
		$erreurs['status'] = _T('mastodon:longueur_maxi_status');
	}

	return
		$erreurs;
}

/**
 * Fonction de traitement du formulaire
 * Envoie la contribution au service configuré
 * 
 * S'il y a une erreur en retour (false), 
 * on affiche un message explicitant qu'il y a une erreur dans la configuration
 */
function formulaires_microbloguer_traiter_dist(){
	$res = array();
	if ($status = _request('status')){
		include_spip('inc/microblog');
		$retour = microblog($status);
		spip_log($retour,'mastodon');
		
		if($retour){
			set_request('status','');
			$res = array('message_ok'=>_T('mastodon:message_envoye')." ".$status,'editable'=>true);
		}else{
			$erreur = _T('mastodon:erreur_verifier_configuration');
			if (defined('_TEST_MICROBLOG_SERVICE') AND !_TEST_MICROBLOG_SERVICE)
				$erreur = _T('mastodon:erreur_envoi_desactive');
			$res = array('message_erreur'=>$erreur,'editable'=>true);
		}
	}
	else
		$res = array('message_erreur'=>'???','editable'=>true);

	return
		$res;
}

?>
