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

if (!defined("_MASTODON_API_CALL_MICROCACHE_DELAY")) define("_MASTODON_API_CALL_MICROCACHE_DELAY",180);

include_spip('lib/tootophp/autoload');

/**
 * URL authorize de redirection apres identification oAuth
 * @param string $domain
 * @return string
 */
function mastodon_oauth_redirect_uri_authorize($domain) {
	if (!function_exists('url_absolue')) {
		include_spip('inc/filres_mini');
	}
	return url_absolue(_DIR_RACINE . "mastodon_oauth.api/authorize/$domain");
}

/**
 * Charger l'application pour le domaine pour demarrer le dialogue oAuth
 * @param string $domain
 * @return TootoPHP\TootoPHP|bool
 */
function mastodon_oauth_load_app($domain) {
	$redirect_authorize = mastodon_oauth_redirect_uri_authorize($domain);
	$dir_credentials = sous_repertoire(_DIR_ETC, 'credentials');

	$tootoPHP = new TootoPHP\TootoPHP($domain, $dir_credentials);

	// App name and website
	$app_name = "SpipToMastodon";
	try {
		$app = $tootoPHP->registerApp($app_name, $GLOBALS['meta']['adresse_site'], $redirect_authorize);
	}
	catch (Exception $e) {
		spip_log('mastodon_oauth_load_app:'.$e->getMessage(), 'mastodon' . _LOG_ERREUR);
		return false;
	}

	return $app;
}

/**
 * Enregistrer un nouvel utilisateur avec son token, pour resservir plus tard
 * @param $domain
 * @param $code
 * @param $redirect_authorize
 * @return bool|string|array
 *   array if successful, false or error message otherwise
 */
function mastodon_oauth_register_user($domain, $code, $redirect_authorize) {

	if ($app = mastodon_oauth_load_app($domain)
	  and $code){
		try {
			$token = $app->registerAccessToken($code, $redirect_authorize, false);
		} catch (Exception $e) {
			spip_log('mastodon_oauth_register_user:'.$e->getMessage(), 'mastodon' . _LOG_ERREUR);
			return $e->getMessage();
		}

		if ($token
		  and $user = $app->getUser()) {


			include_spip('inc/config');
			// username avec instance pour eviter les collisions
			$username = $user['username'] . '@' . $domain;
			ecrire_config('mastodon/accounts/'.$username, $token);

			// initialiser le compte par defaut si pas de compte valide par defaut
			$default = lire_config('mastodon/default_account', '');
			if (!$default  or !lire_config('mastodon/accounts/'.$default, '')) {
				ecrire_config('mastodon/default_account', $username);
			}

			return $user;
		}

		spip_log("Echec account/verify_credentials lors de l'ajout d'un compte","mastodon"._LOG_ERREUR);
	}

	return false;
}

/**
 * Get user_name&token for user_name given (or default if none given)
 *
 * @param string $user_name
 * @return array|bool
 */
function mastodon_oauth_user_token($user_name = '') {
	static $user_tokens = array();

	if (isset($user_tokens[$user_name])) {
		return $user_tokens[$user_name];
	}

	if (!function_exists('lire_config')) {
		include_spip('inc/config');
	}

	// si pas de user_name fourni on utilise le compte par defaut
	$account = $user_name;
	if (!$account) {
		if (!$account = lire_config('mastodon/default_account')) {
			spip_log("mastodon_oauth_user_token : aucun user_name par defaut dans mastodon/default_account","mastodon"._LOG_ERREUR);
			return $user_tokens[$user_name] = false;
		}
	}

	// si on l'a en config c'est tout bon
	if ($token = lire_config('mastodon/accounts/' . $account)) {
		return $user_tokens[$user_name] = $user_tokens[$account] = array($account, $token);
	}

	// sinon on regarde si on a une fonction connue pour recuperer le token de cet user
	if ($mastodon_oauth_user_token = charger_fonction('mastodon_oauth_user_token', 'inc', true)) {
		return $user_tokens[$user_name] = $user_tokens[$account] = $mastodon_oauth_user_token($account);
	}

	// c'est un echec
	spip_log("mastodon_oauth_user_token : aucun account $user_name ($account) connu","mastodon"._LOG_ERREUR);
	return $user_tokens[$user_name] = $user_tokens[$account] = false;
}

/**
 * Charger l'app oauth authentifee avec une des identites connues
 * @param string $user_name
 * @return TootoPHP\TootoPHP|bool
 */
function mastodon_oauth_load_registered_app($user_name = '') {

	$user = mastodon_oauth_user_token($user_name);
	if (!$user) {
		spip_log("mastodon_oauth_load_registered_app : user_name $user_name inconnu","mastodon"._LOG_ERREUR);
		return false;
	}
	list($user_name, $token) = $user;

	$domain = explode('@', $user_name);
	$domain = end($domain);

	if (!$app = mastodon_oauth_load_app($domain)) {
		spip_log("mastodon_oauth_load_registered_app : impossible de charger app pour domaine $domain","mastodon"._LOG_ERREUR);
		return false;
	}

	// enregistrer le token pour acceder avec cette identite
	$app->authentifyWithToken($token, false);

	return $app;
}


/**
 * Envoyer un message sur Mastodon
 * @param string $status
 * @param array $options
 *   string $user_name permet d'utiliser un user_name specifique
 *   int $max_len longueur maxi des messages
 *   string $visibility public|unlisted|private|direct
 *
 * @return bool|string
 */
function pouet($status, $options = array()){
	// Certains define prennent le pas sur le reste (mode TEST)
	if (defined('_TEST_MICROBLOG_SERVICE')) {
		if (_TEST_MICROBLOG_SERVICE == '') {
			spip_log('pouet() desactive par _TEST_MICROBLOG_SERVICE',"mastodon"._LOG_INFO_IMPORTANTE);
			return false;
		}
	}

	$default_options = array(
		'user_name' => '',
		'max_len' => 500,
		'visibility' => lire_config('mastodon/default_visibility','public'),
	);
	$options = array_merge($default_options, $options);
	// si option visibilite foireuse, on met unlisted (pour pas trop polluer)
	if (!in_array($options['visibility'], array('public','unlisted','private','direct'))) {
		$options['visibility'] = 'unlisted';
	}

	// si pas d'api utilisable on sort
	if (!$user = mastodon_oauth_user_token($options['user_name'])
	  or !$app = mastodon_oauth_load_registered_app(reset($user)))
		return false;

	$max_len = $options['max_len'];
	// Preparer le message (utf8 < 500 caracteres)
	include_spip('inc/charsets');

	// legitime de modifier les espacements ?
	// non, plus avec mastodon ou on a de la place
	// on se contente de trimer
	//$status = trim(preg_replace(', +,', ' ', $status));
	//$status = trim(preg_replace(",\n\n+,", "\n\n", $status));
	$status = trim($status);

	$status = unicode2charset(charset2unicode($status), 'utf-8');
	$status = spip_substr($status, 0, $max_len);

	if (!strlen($status)) {
		spip_log('Rien a pouetter','mastodon');
		return false;
	}

	// anti-begaiment
	$begaie = md5(serialize(array($user,$status)));
	if ($begaie == $GLOBALS['meta']['mastodon_begaie']) {
		spip_log("begaie $status", 'mastodon'._LOG_INFO_IMPORTANTE);
		return false;
	}

	$res = $app->postStatus($status, $options['visibility']);

	if (!$res){
		spip_log('Erreur pouet() : ' . var_export($res, true), 'mastodon'._LOG_ERREUR);
		$res = false;
	}

	// noter l'envoi pour ne pas mastodon 2 fois de suite la meme chose
	if ($res)
		ecrire_meta('mastodon_begaie', $begaie);

	return $res;
}

/**
 * Fonction d'utilisation simple de l'API mastodon oAuth
 *
 * @param $command string : la commande à passer
 * @param $type string : le type de commande (get/post/delete)
 * @param $params array : les paramètres dans un array de la commande
 * @param array $options
 *   bool force : true pour forcer la requete hors cache
 *   string return_type : le retour souhaité par défaut cela renverra la chaine ou l'array retourné par la commande.
 *                        Sinon on peut utiliser les valeurs http_code,http_info,url
 *
 *   mastodon_account : pour utiliser un compte mastodon pre-configure plutot que celui par defaut
 * ou
 *   mastodon_token : token du compte a utiliser
 *   mastodon_token_secret : token secret du compte a utiliser
 * @return bool|string|array
 */
function mastodon_api_call($command,$type='get',$params=array(),$options=null){

	if (!$user = mastodon_oauth_user_token(isset($options['mastodon_account']) ? $options['mastodon_account'] : null)) {
		return false;
	}

	// api_call en cache ?
	$res = false;
	$cache_key = null;
	if ($type !== 'get'
		OR (isset($options['force']) AND $options['force'])
		OR !include_spip("inc/memoization")
	  OR !function_exists("cache_get")
	  OR !$cache_key = "mastodon_api_call-".md5(serialize(array($command,$params,$user)))
	  OR !$res = cache_get($cache_key)
	  OR $res['time']+_MASTODON_API_CALL_MICROCACHE_DELAY<$_SERVER['REQUEST_TIME']){

		if ($app = mastodon_oauth_load_registered_app(reset($user))) {

			try {
				$res = $app->callApi($command, $type, $params);
				if (intval($res['http_code']/100) == 4) {
					$error = "Requete invalide : ".json_encode($res);
					spip_log("mastodon_api_call: $command $error", "mastodon", _LOG_ERREUR);
					return false;
				}
				if ($cache_key and $res['http_code']) {
					cache_set($cache_key,$res,_MASTODON_API_CALL_MICROCACHE_DELAY*2);
				}
			}
			catch (Exception $e) {
				spip_log("mastodon_api_call: $command " . $e->getMessage(),"mastodon" . _LOG_ERREUR);
				if (!$res or $command === 'accounts/verify_credentials') {
					return false;
				}
			}
		}
		else {
			if (!$res) {
				return false;
			}
			spip_log("mastodon_api_call:$command echec connexion, on utilise le cache perime","mastodon"._LOG_INFO_IMPORTANTE);
		}
	}

	$retour = isset($options['return_type'])?$options['return_type']:'content';
	if (!isset($res[$retour]))
		$retour = 'content';

	switch($retour){
		default:
			return $res[$retour];
		case 'content':
			if (!is_string($res['content']) AND is_array($res['content'])) {
				// recopie ?
				$copy = array();
				foreach($res['content'] as $key => $val){
					$copy[$key] = $val;
				}
				return $copy;
			}
			return $res['content'];
			break;
	}
}

/**
 * Verifier que la config mastodon est OK
 * @param bool $complete
 *   verification complete de la connexion, avec requete chez Mastodon (plus lent)
 * @return bool
 */
function mastodon_verifier_config($complete = false){
	if (!$user = mastodon_oauth_user_token())
		return false;
	if ($complete){
		if (!$app = mastodon_oauth_load_registered_app())
			return false;
		if (!$infos = $app->getUser())
			return false;
	}

	return true;
}

function mastodon_account2url($account) {
	// truc@mamot.fr -> //mamot.fr/@truc
	$account = ltrim($account, '@');
	$url = explode('@', $account);
	$username = array_shift($url);
	$instance = array_shift($url);
	$url = "//" . $instance . "/@" . $username;

	return array($username, $instance, $url);
}

function mastodon_url2account($url) {
	$url = explode('://', $url);
	$url = end($url);
	$url = explode('/@', $url);
	$instance = array_shift($url);
	$account = array_shift($url);
	return "$account@$instance";
}

/**
 * Unfollow un compte
 * @param string $account
 * @param string $options
 * @return bool
 */
function mastodon_unfollow($account, $options) {
	// si pas d'api utilisable on sort
	if (!$user = mastodon_oauth_user_token(isset($options['mastodon_account']) ? $options['mastodon_account'] : null)
	  or !$app = mastodon_oauth_load_registered_app(reset($user)))
		return false;

	$followings = $app->getFollowing();

	// truc@mamot.fr -> //mamot.fr/@truc
	list($username, $instance, $url) = mastodon_account2url($account);

	// est-ce que le compte est dans les followings ?
	foreach ($followings as $following) {
		if ($following['username'] === $username
			and strpos($following['url'], $url) !== false) {
			$id = $following['id'];
			$res = $app->callApi("accounts/$id/unfollow", 'post');
			if ($res['http_code'] !== 200
			  or !isset($res['content'])
				or !isset($res['content']['id'])) {
				spip_log("mastodon_unfollow $account : ".var_export($res, true),'mastodon'._LOG_ERREUR);
				return false;
			}

			return true;
		}
	}

	return true;
}

/**
 * Follower un compte si besoin
 * on verifie avant qu'il ne l'est pas deja en memoizant la liste des followings
 * pour permettre plusieurs follows dans un seul hit sans multiplier les appels au serveur mastodon
 *
 * @param string $account
 * @param array $options
 * @return bool
 */
function mastodon_follow_if_not_already($account, $options = array()) {
	static $followings = null;

	// si pas d'api utilisable on sort
	if (!$user = mastodon_oauth_user_token(isset($options['mastodon_account']) ? $options['mastodon_account'] : null)
	  or !$app = mastodon_oauth_load_registered_app(reset($user)))
		return false;

	if (is_null($followings)) {
		$followings = $app->getFollowing();
	}

	// truc@mamot.fr -> //mamot.fr/@truc
	list($username, $instance, $url) = mastodon_account2url($account);

	// est-ce que le compte est deja dans les followings ?
	foreach ($followings as $following) {
		if ($following['username'] === $username
			and strpos($following['url'], $url) !== false) {
			return $following;
		}
	}
	
	// sinon l'ajouter
	$params = array('uri' => $account);
	$res = $app->callApi('follows', 'post', $params);
	spip_log("mastodon_follow_if_not_already : follow $account", 'mastodon');
	if ($res['http_code'] !== 200
	  or !isset($res['content'])
		or !isset($res['content']['id'])) {
		spip_log("mastodon_follow_if_not_already $account : ".var_export($res, true),'mastodon'._LOG_ERREUR);
		return false;
	}
	$following = $res['content'];
	$followings[] = $following;

	return $following;
}

/**
 * Follower automatiquement tous les followers
 * @param array $options
 * @return bool
 */
function mastodon_followback($options = array()) {
	// si pas d'api utilisable on sort
	if (!$user = mastodon_oauth_user_token(isset($options['mastodon_account']) ? $options['mastodon_account'] : null)
	  or !$app = mastodon_oauth_load_registered_app(reset($user)))
		return false;

	$params = array(
		'limit' => 80,
	);
	$maxiter = 100;

	$id = $app->getUserID();
	$method = 'accounts/' . $id . '/followers';
	$res = $app->callApi($method, 'get', $params);
	$followers = $res['content'];
	while($params and $followers and $maxiter-->0) {
		$params = false;
		foreach ($followers as $follower) {
			$account = mastodon_url2account($follower['url']);
			mastodon_follow_if_not_already($account, $options);
		}

		// trouver l'url next
		foreach ($res['headers'] as $header) {
			if (strncmp($header, 'Link:' , 5) == 0) {
				$link = trim(substr($header,5));
				$link = explode(',', $link);
				foreach ($link as $l){
					if (strpos($l,'rel="next"')!==false) {
						$l = explode(';', $l);
						$l = trim(reset($l));
						$l = trim($l,'<> ');
						$l = explode('?', $l);
						parse_str(end($l), $params);
					}
				}
			}
		}

		if ($params) {
			$res = $app->callApi($method, 'get', $params);
			$followers = $res['content'];
		}
	}

	return true;
}

/**
 * Retrouver un utilisateur a partir de son compte
 * @param string $user
 * @param string $host
 * @return array|bool
 */
function mastodon_search_user($user, $host = ''){

	$options = array();
	$url_user = '@' . $user;
	if ($host) {
		$url_user .= '@' . $host;
		$options['resolve'] = true;
	}
	$options['q'] = $url_user;

	$res = mastodon_api_call('accounts/search', 'get', $options);
	if (!$res or !count($res)) {
		return false;
	}

	$res = reset($res);
	return $res;
}

/**
 * Recuperer les status d'un compte ou d'un tag, avec un eventuel filtre
 * @param array $options
 *   string id : id du compte (compte connecte si ni id ni tag fourni)
 *   string tag : tag dont on veut les status
 *   int limit : nombre de status (40 par defaut)
 *   string filter : filtre
 *     with:media > ne retourne que les status avec un media joint
 *   string visibility : filtre
 *     une ou plusieurs visibilite acceptees, separes par une virgule, parmi : public,unlisted,private,direct
 *     par defaut uniquement public
 * @return array
 */
function mastodon_get_statuses($options = array()) {
	// si pas d'api utilisable on sort
	if (!$user = mastodon_oauth_user_token(isset($options['mastodon_account']) ? $options['mastodon_account'] : null)
	  or !$app = mastodon_oauth_load_registered_app(reset($user))) {
		spip_log("mastodon_get_statuses: token ou app invalide", 'mastodon'. _LOG_ERREUR);
		return false;
	}

	$params = array();
	$limit = 40;
	if (isset($options['limit'])) {
		$limit = $options['limit'];
	}
	$maxiter = 100;
	// on sait filtrer les media lors de la requete -> plus efficace
	if (isset($options['filter'])
		and $options['filter'] == 'with:media') {
		$params['only_media'] = true;
	}

	$visibility = 'public';
	if (isset($options['visibility']) and $options['visibility']) {
		$visibility = $options['visibility'];
	}
	$visibility = explode(',', $visibility);
	$visibility = array_map('trim', $visibility);

	$method = false;
	$limit = min($limit, 40); // 40 max par requete
	if (isset($options['id'])) {
		$id = $options['id'];
		$method = 'accounts/' . $id . '/statuses';
	}
	if (isset($options['tag'])) {
		$tag = $options['tag'];
		$method = 'timelines/tag/' . $tag;
	}
	if (!$method) {
		$id = $app->getUserID();
		$method = 'accounts/' . $id . '/statuses';
	}
	$params['limit'] = $limit;

	$all_statuses = array();
	$res = $app->callApi($method, 'get', $params);
	$statuses = $res['content'];
	if (!is_array($statuses)) {
		spip_log("contenu innatendu : " . json_encode($res), "mastodondbg" . _LOG_ERREUR);
	}
	if ($statuses and is_array($statuses)) {
		do {
			$s = array_shift($statuses);
			$ok = (in_array($s['visibility'], $visibility) ? true : false);
			if ($ok and isset($options['filter']) and $options['filter']) {
				$ok = false;
				if ($options['filter'] == 'with:media' and count($s['media_attachments'])) {
					$ok = true;
				}
			}
			if ($ok) {
				$all_statuses[] = $s;
			}

			if (!$statuses and count($all_statuses) < $limit) {
				$params = false;
				// trouver l'url next
				foreach ($res['headers'] as $header) {
					if (strncmp($header, 'Link:', 5) == 0) {
						$link = trim(substr($header, 5));
						$link = explode(',', $link);
						foreach ($link as $l) {
							if (strpos($l, 'rel="next"') !== false) {
								$l = explode(';', $l);
								$l = trim(reset($l));
								$l = trim($l, '<> ');
								$l = explode('?', $l);
								parse_str(end($l), $params);
							}
						}
					}
				}

				if ($params) {
					$res = $app->callApi($method, 'get', $params);
					$statuses = $res['content'];
					$maxiter--;
				}
			}
		} while ($statuses and $maxiter > 0 and count($all_statuses) < $limit);
	}

	return $all_statuses;
}