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

		$cfg = @unserialize($GLOBALS['meta']['microblog']);
		if (isset($cfg['mastodon_accounts'][$account])){
			$cfg['default_account'] = $account;

			ecrire_meta("microblog", serialize($cfg));
		}
	}
}
?>