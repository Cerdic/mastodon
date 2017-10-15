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
 * Fonction vérifiant le retour de mastodon
 * Elle met dans la configuration du plugin les tokens
 * nécessaires pour de futures connexions
 */
function action_api_mastodon_oauth_dist(){

	include_spip('inc/mastodon');
	include_spip('inc/session');

	$arg = _request('arg');
	$arg = explode('/', $arg);

	$method = array_shift($arg);
	if (in_array($method, array('authorize'))) {
		$mastodon_oauth = "mastodon_oauth_" . $method;
		$mastodon_oauth($arg);
	}

}


function mastodon_oauth_authorize($arg) {
	include_spip('inc/mastodon');

	$domain = reset($arg);
	$redirect_authorize = url_absolue(self());
	$redirect_authorize = explode('?', $redirect_authorize);
	$redirect_authorize = reset($redirect_authorize);

	$code = _request('code');

	$user = mastodon_oauth_register_user($domain, $code, $redirect_authorize);
	$GLOBALS['redirect'] = generer_url_ecrire('configurer_mastodon');
	if (is_string($user)) {
		$GLOBALS['redirect'] = parametre_url($GLOBALS['redirect'], 'erreur', $user);
	}
}

