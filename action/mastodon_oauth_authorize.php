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
function action_mastodon_oauth_authorize_dist(){

	include_spip('inc/mastodonoauthspip');
	include_spip('inc/session');

	$redirect = session_get('mastodon_redirect') ? session_get('mastodon_redirect') : $GLOBALS['meta']['url_site_spip'];
	if (isset($GLOBALS['visiteur_session']['oauth_token'])
		AND $GLOBALS['visiteur_session']['oauth_token']){

		if(_request('denied')){
			spip_log("action_mastodon_oauth_authorize_dist : denied",'mastodon'._LOG_ERREUR);
			$redirect = parametre_url($redirect,'erreur','auth_denied','&');
			session_set('oauth_status','denied');
			$GLOBALS['redirect'] = $redirect;
		}
		elseif (_request('oauth_token') && ($GLOBALS['visiteur_session']['oauth_token'] !== _request('oauth_token'))) {
			spip_log("action_mastodon_oauth_authorize_dist : old_token",'mastodon'._LOG_ERREUR);
			$redirect = parametre_url($redirect,'erreur','old_token','&');
			session_set('oauth_status','oldtoken');
			$GLOBALS['redirect'] = $redirect;
		}
		else {
			$cfg = @unserialize($GLOBALS['meta']['microblog']);
			$consumer_key = $cfg['mastodon_consumer_key'];
			$consumer_secret = $cfg['mastodon_consumer_secret'];

			$connection = new MastodonOAuthSPIP($consumer_key, $consumer_secret, $GLOBALS['visiteur_session']['oauth_token'], $GLOBALS['visiteur_session']['oauth_token_secret']);
			$access_token = $connection->getAccessToken(_request('oauth_verifier'));
			session_set('access_token',$access_token);

			/**
			 * Si le code de retour est 200 :
			 * L'utilisateur a été vérifié et
			 * les tokens d'accès peuvent être sauvegardés pour un usage futur
			 * on appelle la callback en session qui en fait ce qu'elle veut
			 */
			if (200 == $connection->http_code) {

				if ($callback = session_get('mastodon_callback')
				  AND $callback = charger_fonction($callback,"action",true)){
					// si la callback retourne quelque chose c'est une url de redirect
					if ($r = $callback(true, $redirect))
						$redirect = $r;
				}

				$GLOBALS['redirect'] = $redirect;
			}
			else {
				spip_log("Erreur '".$connection->http_code."' au retour pour recuperation des tokens dans action_mastodon_oauth_callback_dist",'mastodon'._LOG_ERREUR);
				// peut donner une info en plus, genre un message d'erreur a la place des tokens
				spip_log($access_token,'mastodon'._LOG_ERREUR);
				$redirect = parametre_url($redirect,'erreur_code',$connection->http_code);
				if (count($access_token)==1
				  AND $e = trim(implode(" ",array_keys($access_token))." ".implode(" ",array_values($access_token)))){
					session_set("oauth_erreur_message","Erreur : $e");
					$redirect = parametre_url($redirect,'erreur','erreur_oauth','&');
				}
				else {
					$redirect = parametre_url($redirect,'erreur','auth_denied','&');
				}
				$GLOBALS['redirect'] = $redirect;
			}
		}
	}
	else {
		// rien a faire ici !
		$GLOBALS['redirect'] = $redirect;
	}

	// vider la session
	foreach(array('access_token','oauth_token','oauth_token_secret','mastodon_redirect','mastodon_callback') as $k)
		if (isset($GLOBALS['visiteur_session'][$k]))
			session_set($k);
}

function mastodon_oauth_authorize($callback, $redirect, $sign_in=true){
	$cfg = @unserialize($GLOBALS['meta']['microblog']);

	$redirect = parametre_url(parametre_url($redirect,'erreur_code',''),'erreur','','&');

	include_spip('inc/filtres');
	include_spip('inc/mastodonoauthspip');
	include_spip('inc/session');

	/**
	 * L'URL de callback qui sera utilisée suite à la validation chez mastodon
	 * Elle vérifiera le retour et finira la configuration
	 */
	$oauth_callback = url_absolue(generer_url_action('mastodon_oauth_authorize','',true));

	/**
	 * Récupération des tokens depuis mastodon par rapport à notre application
	 * On les place dans la session de l'individu en cours
	 * Ainsi que l'adresse de redirection pour la seconde action
	 */
	try {
		$connection = new MastodonOAuthSPIP($cfg['mastodon_consumer_key'], $cfg['mastodon_consumer_secret']);
		$request_token = $connection->getRequestToken($oauth_callback);
		$token = $request_token['oauth_token'];
		session_set('oauth_token',$token);
		session_set('oauth_token_secret',$request_token['oauth_token_secret']);
		session_set('mastodon_redirect',str_replace('&amp;','&',$redirect));
		session_set('mastodon_callback',$callback);

		/**
		 * Vérification du code de retour
		 */
		switch ($code = $connection->http_code) {
			/**
			 * Si le code de retour est 200 (ok)
			 * On envoie l'utilisateur vers l'url d'autorisation
			 */
			case 200:
				$url = $connection->getAuthorizeURL($token, $sign_in);
				include_spip('inc/headers');
				$GLOBALS['redirect'] = $url;
				#echo redirige_formulaire($url);
				break;
			/**
			 * Sinon on le renvoie vers le redirect avec une erreur
			 */
			default:
				spip_log('Erreur connexion mastodon','mastodon'._LOG_ERREUR);
				spip_log($connection, 'mastodon'._LOG_ERREUR);
				$redirect = parametre_url($redirect,'erreur_code',$code);
				$redirect = parametre_url($redirect,'erreur','erreur_conf_app','&');
				$GLOBALS['redirect'] = $redirect;
				break;
		}
	}
	catch(Exception $e){
		session_set('oauth_erreur_message',$e->getMessage());
		$redirect = parametre_url($redirect,'erreur',"erreur_oauth",'&');
		$GLOBALS['redirect'] = $redirect;
	}
}
?>