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
function formulaires_editer_pouet_charger_dist($objet,$id_objet,$hide_form=false){
	$primary = id_table_objet($objet);

	$valeurs = array();
	$valeurs['_hide'] = (($hide_form AND is_null(_request('pouet')))?' ':'');
	$valeurs['objet'] = $objet;
	$valeurs['id_objet'] = $id_objet;
	$valeurs['pouet'] = recuperer_fond("modeles/mastodon_instituer" . $objet, array($primary => $id_objet));
	$valeurs['_status'] = trim($valeurs['pouet']);

	include_spip("inc/mastodon");
	if (!mastodon_verifier_config()){
		$valeurs['editable'] = false;
		$valeurs['message_erreur'] = _T('mastodon:erreur_config_pour_tweeter');
	}
	$valeurs['_max_len'] = 500;

	return $valeurs;
}

/**
 * Fonction de vérification du formulaire avant traitement
 * 
 * Vérifie la présence d'un statut depuis le champs adéquat
 * Vérifie que la longueur du statut n'excède pas la longueur maximale
 */
function formulaires_editer_pouet_verifier_dist($objet,$id_objet){
	include_spip('inc/charsets');
	$erreurs = array();
	$pouet = _request('pouet');
	if (spip_strlen($pouet)>500){
		$erreurs['pouet'] = _T('mastodon:longueur_maxi_status');
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
function formulaires_editer_pouet_traiter_dist($objet,$id_objet){
	$res = array(
		'editable'=>true
	);

	$pouet = _request('pouet');

	if (_request('annuler_pouet')) {
		$pouet = " ";// ruse pour ne rien envoyer
	}

	if (!is_null($pouet)){
		$set = array('pouet' => $pouet);
		include_spip('action/editer_objet');
		objet_modifier($objet, $id_objet, $set);
	}

	if (!strlen(trim($pouet))) {
		set_request('pouet');
	}

	if (_request('envoyer')){
		include_spip('inc/mastodon');
		$primary = id_table_objet($objet);
		$status = recuperer_fond("modeles/mastodon_instituer" . $objet, array($primary => $id_objet));
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
		else{
			$res['message_erreur']=_T('mastodon:erreur_verifier_configuration');
		}
	}

	return $res;
}

