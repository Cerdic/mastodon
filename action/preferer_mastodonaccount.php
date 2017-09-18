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
 * Supprimer un utilisateur Mastodon associe a l'application
 *
 * @param null|string $account
 */
function action_preferer_mastodonaccount_dist($account = null) {
	if (is_null($account)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$account = $securiser_action();
	}

	include_spip("inc/autoriser");
	if(autoriser("preferer","mastodonaccount",$account)){

		include_spip("inc/config");
		include_spip("inc/mastodon");
		if (mastodon_oauth_user_token($account)) {
			ecrire_config('mastodon/default_account', $account);
		}
	}
}

