<?php
/*
 * Plugin spip|mastodon
 * (c) 2009-2013
 *
 * envoyer et lire des messages de Mastodon
 * distribue sous licence GNU/LGPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

/**
 * Analyse d'une URL d'un flux mastodon, via l'API
 * @param string $url_syndic
 * @return array|string
 */
function syndic_mastodon_dist($url_syndic) {

	$parts = parse_url($url_syndic);
	$items = false;

	if (strncmp($parts['path'],$p = '/users/', 7) == 0
	  or strncmp($parts['path'],$p = '/@', 2) == 0) {
		$quoi = substr($parts['path'], strlen($p));
		$items = mastodon_syndiquer_user($quoi, $parts);
	}
	elseif (strncmp($parts['path'],$p = '/tags/', 6) == 0) {
		$quoi = substr($parts['path'], strlen($p));

		// on sait syndiquer plusieurs tags d'un coup, separes par un # : https://mamot.fr/tags/pouetradio#tootradio#soundcheck
		if (isset($parts['fragment']) and $parts['fragment']) {
			$quoi .= '#' . $parts['fragment'];
		}
		$tags = explode('#', $quoi);
		$items = array();
		foreach ($tags as $tag) {
			$i = mastodon_syndiquer_tag($tag, $parts);
			$items = array_merge($items, $i);
		}
	}
	else {
		spip_log("syndication $url_syndic non prise en charge", 'mastodon' . _LOG_ERREUR);
	}

	if (!$items) {
		$items = _T('sites:avis_echec_syndication_02');
	}

	return $items;
}


/**
 * @param string $raw_data
 * @param string $raw_format
 * @return array
 */
function syndic_mastodon_raw_data_to_array_dist($raw_data, $raw_format) {
	$data = array();
	if ($raw_format == 'json') {
		$data = json_decode($raw_data, true);
	}
	return $data;
}


/**
 * Syndiquer un tag
 * @param string $tag
 * @param array $url_parts
 * @return array|bool
 */
function mastodon_syndiquer_tag($tag, $url_parts) {
	include_spip('inc/mastodon');

	$options = array();
	if (isset($url_parts['user'])) {
		$options['mastodon_account'] = $url_parts['user'];
	}

	$options['tag'] = $tag;

	$statuses = mastodon_get_statuses($options);
	if (!$statuses) {
		return false;
	}

	return mastodon_statuses_to_items($statuses);
}

/**
 * Syndiquer un user
 * @param string $qui
 * @param $url_parts
 * @return array|bool
 */
function mastodon_syndiquer_user($qui, $url_parts) {
	include_spip('inc/mastodon');

	$qui = explode('/', $qui);

	$options = array();
	if (isset($url_parts['user'])) {
		$options['mastodon_account'] = $url_parts['user'];
	}
	if (isset($qui[1]) and $qui[1]=='media') {
		$options['filter'] = 'with:media';
	}


	// retrouver l'id de l'utilisateur
	if (!$user = mastodon_search_user($qui[0], $url_parts['host'])) {
		return false;
	}
	$options['id'] = $user['id'];

	$statuses = mastodon_get_statuses($options);
	if (!$statuses) {
		return false;
	}

	return mastodon_statuses_to_items($statuses);
}

/**
 * Enjoliver l'URL d'un media affichee dans le lien enclosure
 * @param string $url
 * @return string
 */
function mastodon_media_jolie_url($url) {
	$parts = parse_url($url);

	$path = ltrim($parts['path'],'/');
	if (count(explode('/', $path))>2) {
		$path = '…/' . basename($path);
	}

	return $parts['host'] . '/' . $path;
}

/**
 * Traiter le tableau de status et renseigner les infos des items syndiques
 * @param array $statuses
 * @return array
 */
function mastodon_statuses_to_items($statuses) {
	static $mimes_type = array();

	$items = array();

	foreach ($statuses as $status) {
		$data['raw_data'] = $status;
		$data['raw_format'] = 'json';

		$data['titre'] = '@' . $status['account']['acct'] . ' : ' .couper($status['content'],60);
		$data['url'] = $status['uri'];
		$data['lang'] = $status['language'];
		$data['content'] = $status['content'];
		$data['date'] = strtotime($status['created_at']);


		$enclosures = array();
		foreach ($status['media_attachments'] as $media) {
			$url = $media['url'];
			$texte_url = mastodon_media_jolie_url($media['text_url']);
			$type = 'image/jpeg';
			if (preg_match(',[.](\w+)($|\?),Uims', $url, $m)) {
				$extension = $m[1];
				if ((isset($mimes_type[$extension]) and $mime = $mimes_type[$extension])
					or $mime = sql_getfetsel('mime_type', 'spip_types_documents', 'extension='.sql_quote($extension))
				  or $mime = sql_getfetsel('mime_type', 'spip_types_documents', 'mime_type LIKE '.sql_quote('%/'.$extension))) {
					$type = $mimes_type[$extension] = $mime;
				}
			}

			$s = '<a rel="enclosure"'
			. ($url ? ' href="' . spip_htmlspecialchars($url) . '"' : '')
			. ($type ? ' type="' . spip_htmlspecialchars($type) . '"' : '')
			. '>' . $texte_url . '</a>';

			$enclosures[] = $s;
		}
		$data['enclosures'] = implode(', ', array_unique($enclosures));

		$tags = array();
		foreach ($status['tags'] as $tag) {
			$tags[] = '<a rel="directory" href="' . $tag['url'] . '">#' . $tag['name'] . '</a>';
		}
		$data['tags'] = implode(', ', array_unique($tags));

		$items[] = $data;
	}

	return $items;
}