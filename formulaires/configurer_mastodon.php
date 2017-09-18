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


function formulaires_configurer_mastodon_verifier_dist(){

	$erreurs = array();

	if (_request('append_account')) {
		refuser_traiter_formulaire_ajax();
		$host = _request('host');
		if (!$host) {
			$host = 'https://mamot.fr';
		}
		include_spip('inc/mastodon');
		$domain = parse_url($host, PHP_URL_HOST);
		$redirect_uri = mastodon_oauth_redirect_uri_authorize($domain);
		if ($app = mastodon_oauth_load_app($domain)
		  and $url = $app->getAuthUrl($redirect_uri)) {
			include_spip('inc/headers');
			redirige_par_entete($url);
		}
		$erreurs['host'] = _T('mastodon:erreur_creation_application');
	}

	return $erreurs;
}

