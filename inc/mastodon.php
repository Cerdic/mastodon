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

/**
 * Envoyer un message sur Mastodon
 * @param $status
 * @param null $tokens
 *   permet d'utiliser des tokens specifiques et pas ceux pre-configures
 *   (voir mastodon_connect)
 * @return bool|string
 */
function tweet($status, $tokens = null){
	// Certains define prennent le pas sur le reste (mode TEST)
	if (defined('_TEST_MICROBLOG_SERVICE')) {
		if (_TEST_MICROBLOG_SERVICE == '') {
			spip_log('microblog desactive par _TEST_MICROBLOG_SERVICE',"mastodon"._LOG_INFO_IMPORTANTE);
			return false;
		}
	}

	/**
	 * Si l'API utilisée est mastodon, on force le passage en oAuth
	 */
	$oAuthConnection = mastodon_connect($tokens);

	// si pas d'api utilisable on sort
	if (!$oAuthConnection)
		return false;
	
	// Preparer le message (utf8 < 140 caracteres)
	include_spip('inc/charsets');
	$status = trim(preg_replace(',\s+,', ' ', $status));
	$status = unicode2charset(charset2unicode($status), 'utf-8');
	$status = substr($status, 0, 140);

	if (!strlen($status)) {
		spip_log('Rien a bloguer','mastodon');
		return false;
	}

	$datas = array('status' => $status);

	// anti-begaiment
	$begaie = md5(serialize(array($tokens,$status)));
	if ($begaie == $GLOBALS['meta']['mastodon_begaie']) {
		spip_log("begaie $status", 'mastodon'._LOG_INFO_IMPORTANTE);
		return false;
	}

	// ping et renvoyer la reponse xml
	$ret = 'ok';
	$api = 'statuses/update';
	$oAuthConnection->post($api,$datas);
	if (200 != $oAuthConnection->http_code){
		spip_log('Erreur '.$oAuthConnection->http_code,'mastodon');
		$ret = false;
	}

	// noter l'envoi pour ne pas mastodon 2 fois de suite la meme chose
	if ($ret)
		ecrire_meta('mastodon_begaie', $begaie);

	return $ret;
}

/**
 * @param null|array $tokens
 *   mastodon_consumer_key : key de l'application a utiliser
 *   mastodon_consumer_secret : secret de l'application a utiliser
 *
 *   mastodon_account : pour utiliser un compte mastodon pre-configure plutot que celui par defaut
 * ou
 *   mastodon_token : token du compte a utiliser
 *   mastodon_token_secret : token secret du compte a utiliser
 *
 *
 * @return bool|MastodonOAuthSPIP
 */
function mastodon_connect($tokens=null){
	static $connection = null;

	$t = md5(serialize($tokens));
	if (!isset($connection[$t])){

		if($tokens = mastodon_tokens($tokens)){
			// Cas de mastodon et oAuth
			$t2 = md5(serialize($tokens));
			include_spip('inc/mastodonoauthspip');
			$connection[$t] = $connection[$t2] = new MastodonOAuthSPIP(
				$tokens['mastodon_consumer_key'],
				$tokens['mastodon_consumer_secret'],
				$tokens['mastodon_token'],
				$tokens['mastodon_token_secret']);

			if(!$connection[$t2]) {
				spip_log('Erreur de connexion à mastodon, verifier la configuration','mastodon'._LOG_ERREUR);
				return false;
			}
		}
		else{
			spip_log('Erreur de connexion à mastodon, verifier la configuration','mastodon'._LOG_ERREUR);
			return false;
		}
	}
	return $connection[$t];
}

/**
 * Determiner les tokens de connexion en fonction de ceux passes
 * et de la configuration par defaut
 *
 * @param array $tokens
 * @return array
 */
function mastodon_tokens($tokens=null){
	$cfg = @unserialize($GLOBALS['meta']['microblog']);
	if (!$cfg AND !$tokens) return false;
	if (!$cfg) $cfg = array();

	if(!is_array($tokens))
		$tokens = array();

	$t = array_intersect_key($tokens,
		array(
			'mastodon_consumer_key'=>'',
			'mastodon_consumer_secret'=>'',
			'mastodon_account'=>'',
			'mastodon_token'=>'',
			'mastodon_token_secret'=>'',
		));

	if (!isset($t['mastodon_consumer_key']) OR !isset($t['mastodon_consumer_secret'])){
		$t['mastodon_consumer_key'] = $cfg['mastodon_consumer_key'];
		$t['mastodon_consumer_secret'] = $cfg['mastodon_consumer_secret'];
	}

	if (!isset($t['mastodon_token']) OR !isset($t['mastodon_token_secret'])){
		$account = $cfg['default_account'];
		if (isset($t['mastodon_account']) AND isset($cfg['mastodon_accounts'][$t['mastodon_account']]))
			$account = $t['mastodon_account'];

		if (!isset($cfg['mastodon_accounts'][$account]) AND isset($cfg['mastodon_accounts'])){
			$accounts = array_keys($cfg['mastodon_accounts']);
			$account = reset($accounts);
		}

		if (isset($cfg['mastodon_accounts'][$account])){
			$t['mastodon_token'] = $cfg['mastodon_accounts'][$account]['token'];
			$t['mastodon_token_secret'] = $cfg['mastodon_accounts'][$account]['token_secret'];
		}
	}
	if(
		isset($t['mastodon_consumer_key'])
		  AND isset($t['mastodon_consumer_secret'])
		  AND isset($t['mastodon_token'])
		  AND isset($t['mastodon_token_secret'])){
		return $t;
	}

	return false;
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
 *   mastodon_consumer_key : key de l'application a utiliser
 *   mastodon_consumer_secret : secret de l'application a utiliser
 *
 *   mastodon_account : pour utiliser un compte mastodon pre-configure plutot que celui par defaut
 * ou
 *   mastodon_token : token du compte a utiliser
 *   mastodon_token_secret : token secret du compte a utiliser
 * @return bool|string|array
 */
function mastodon_api_call($command,$type='get',$params=array(),$options=null){
	include_spip('inc/microblog');

	// api_call en cache ?
	$cache_key = null;
	if ($type !== 'get'
		OR (isset($options['force']) AND $options['force'])
		OR !include_spip("inc/memoization")
	  OR !function_exists("cache_get")
	  OR !$t = mastodon_tokens($options)
	  OR !$cache_key = "mastodon_api_call-".md5(serialize(array($command,$params,$t)))
	  OR !$res = cache_get($cache_key)
	  OR $res['time']+_MASTODON_API_CALL_MICROCACHE_DELAY<$_SERVER['REQUEST_TIME']){

		if ($connection = mastodon_connect($options)){

			$res = array();
			switch($type){
				case 'post':
					$res['content'] = $connection->post($command,$params);
					break;
				case 'delete':
					$res['content'] = $connection->delete($command,$params);
					break;
				case 'get':
				default:
					$res['content'] = $connection->get($command,$params);
					break;
			}
			$res['http_code'] = $connection->http_code;
			$res['http_info'] = $connection->http_info;
			$res['url'] = $connection->url;
			$res['time'] = $_SERVER['REQUEST_TIME'];

			if ($cache_key)
				cache_set($cache_key,$res,_MASTODON_API_CALL_MICROCACHE_DELAY*2);
		}
		else {
			if (!$res)
				return false;
			spip_log("mastodon_api_call:$command echec connexion, on utilise le cache perime","mastodon".LOG_INFO_IMPORTANTE);
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
	if (!$tokens = mastodon_tokens())
		return false;
	if ($complete){
		if (!mastodon_connect())
			return false;
		if (!$infos = mastodon_api_call("account/verify_credentials"))
			return false;
	}

	return true;
}