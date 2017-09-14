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
 * Ajouter un utilisateur
 * Il faut lancer une demande d'autorisation chez Mastodon (1er appel)
 * Au second appel (avec $is_callback=true) on recupere les tokens et on ajoute l'utilisateur
 * a la config du plugin
 */
function action_ajouter_mastodonaccount_dist($is_callback = false) {
	include_spip("inc/autoriser");
	if(autoriser("ajouter","mastodonaccount")){
		if (!$is_callback){
			// au premier appel
			$securiser_action = charger_fonction('securiser_action', 'inc');
			$arg = $securiser_action();

			include_spip("inc/autoriser");
			if(autoriser("ajouter","mastodonaccount")){

				// lancer la demande d'autorisation en indiquant le nom de l'action qui sera rappelee au retour
				include_spip("action/mastodon_oauth_authorize");
				mastodon_oauth_authorize("ajouter_mastodonaccount",_request('redirect'));
			}
		}
		else {
			// appel au retour de l'authorize
			// recuperer le screenname
			$tokens = array(
				'mastodon_token' => $GLOBALS['visiteur_session']['access_token']['oauth_token'],
				'mastodon_token_secret' => $GLOBALS['visiteur_session']['access_token']['oauth_token_secret'],
			);
			// ajouter le compte aux preferences
			mastodon_ajouter_mastodonaccount($tokens);
		}
	}
}

/**
 * Ajouter un compte dans la liste des comptes dispos
 * a partir de ses tokens (meme format que dans mastodon_connect()
 *
 * @param array $tokens
 *   mastodon_token : token du compte a utiliser
 *   mastodon_token_secret : token secret du compte a utiliser
 * @return array
 */
function mastodon_ajouter_mastodonaccount($tokens){
	$cfg = @unserialize($GLOBALS['meta']['microblog']);

	include_spip("inc/mastodon");
	$options = $tokens;
	$options['force'] = true;
	if ($res = mastodon_api_call("account/verify_credentials","get",array(),$options)){
		$cfg['mastodon_accounts'][$res['screen_name']] = array(
			'token' => $tokens['mastodon_token'],
			'token_secret' => $tokens['mastodon_token_secret'],
		);
	}
	else {
		$cfg['mastodon_accounts'][] = array(
			'token' => $tokens['mastodon_token'],
			'token_secret' => $tokens['mastodon_token_secret'],
		);
		spip_log("Echec account/verify_credentials lors de l'ajout d'un compte","mastodon"._LOG_ERREUR);
	}
	if (!isset($cfg['default_account'])
	  OR !isset($cfg['mastodon_accounts'][$cfg['default_account']])){
		$accounts = array_keys($cfg['mastodon_accounts']);
		$cfg['default_account'] = reset($accounts);
	}

	ecrire_meta("microblog", serialize($cfg));

	return $cfg;
}
?>