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
 * Boucle (DATA){
 * @param $url
 * @param array $options
 * @return array|bool|string
 */
function inc_mastodon_to_array_dist($url, $options = array()) {

	$url = parse_url($url);

	$command = $url['path'];
	$params = array();
	parse_str($url['query'],$params);

	if (isset($params['mastodon_account'])) {
		$options['mastodon_account'] = $params['mastodon_account'];
		unset($params['mastodon_account']);
	}

	if (!function_exists('mastodon_api_call'))
		include_spip("inc/mastodon");

	$res = mastodon_api_call($command,'get',$params,$options);

	return $res;
}
