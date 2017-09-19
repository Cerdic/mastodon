<?php
/*
 * Plugin spip|mastodon
 * (c) 2009-2013
 *
 * envoyer et lire des messages de Mastodon
 * distribue sous licence GNU/LGPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")){
	return;
}

/**
 * Fonction de chargement des valeurs par defaut des champs du formulaire
 */
function formulaires_pouetter_charger_dist(){
	$valeurs =
		array(
			'status' => '',
			'_max_len' => 500,
		);
	include_spip("inc/mastodon");
	if (!mastodon_verifier_config()){
		$valeurs['editable'] = false;
		$valeurs['message_erreur'] = _T('mastodon:erreur_config_pour_pouetter');
	}
	return $valeurs;
}

/**
 * Fonction de vérification du formulaire avant traitement
 *
 * Vérifie la présence d'un statut depuis le champs adéquat
 * Vérifie que la longueur du statut n'excède pas la longueur maximale
 */
function formulaires_pouetter_verifier_dist(){
	include_spip('inc/charsets');
	$erreurs = array();

	if (!$status = _request('status')){
		$erreurs['status'] = _T('info_obligatoire');
	} elseif (spip_strlen($status)>500) {
		$erreurs['status'] = _T('mastodon:longueur_maxi_status');
	}

	return $erreurs;
}

/**
 * Fonction de traitement du formulaire
 * Envoie la contribution au service configuré
 *
 * S'il y a une erreur en retour (false),
 * on affiche un message explicitant qu'il y a une erreur dans la configuration
 */
function formulaires_pouetter_traiter_dist(){
	$res = array(
		'editable' => true,
	);

	if ($status = _request('status')){
		include_spip('inc/mastodon');
		$retour = pouet($status);

		if($retour and isset($retour['id']) and $retour['id']){
			if (isset($retour['content'])) {
				$status = $retour['content'];
			}
			else {
				$status = nl2br($status);
			}
			$res['message_ok']=_T('mastodon:message_envoye') . " <blockquote>$status</blockquote>";
		}
		else {
			$erreur = _T('mastodon:erreur_verifier_configuration');
			if (defined('_TEST_MICROBLOG_SERVICE') AND !_TEST_MICROBLOG_SERVICE){
				$erreur = _T('mastodon:erreur_envoi_desactive');
			}
			$res['message_erreur'] = $erreur;
		}

	}

	return $res;
}

