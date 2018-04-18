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
		$data['raw_data'] = json_encode($status);
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
			$name = '#' . $tag['name'];
			$tags[strtolower($name).$tag['url']] = '<a rel="tag" href="' . $tag['url'] . '">#' . $tag['name'] . '</a>';
		}

		// Trouver les microformats dans le content
		if (preg_match_all(
			',<a[[:space:]]([^>]+[[:space:]])?rel=[^>]+>.*</a>,Uims',
			$data['content'], $regs, PREG_PATTERN_ORDER)) {
			foreach ($regs[0] as $r) {
				$name = trim(supprimer_tags($r));
				$url = extraire_attribut($r, 'href');
				$tags[strtolower($name) . $url] = $r;
			}
		}

		$data['tags'] = array_values($tags);

		$items[] = $data;
	}

	return $items;
}


/**
 * Rechercher tous les status syndiques sans raw_data (a l'ancienne via un RSS)
 * @param int $limite
 */
function mastodon_update_anciens_articles_syndiques($limite=100) {

	// les sites en syndication mastodon
	$id_syndic = sql_allfetsel('id_syndic', 'spip_syndic', 'syndication='.sql_quote('oui').' AND url_syndic like '.sql_quote('mastodon:%'));
	$id_syndic = array_map('reset', $id_syndic);

	// les articles syndiqués avec raw_data vide
	$where = sql_in('id_syndic', $id_syndic) . ' AND raw_data=\'\' AND statut=' . sql_quote('publie');
	$nb = sql_countsel('spip_syndic_articles', $where);
	spip_log('mastodon_update_anciens_articles_syndiques : '.$nb.' articles syndiques sans raw_data', 'mastodon'._LOG_INFO_IMPORTANTE);
	$articles = sql_allfetsel('id_syndic_article, url', 'spip_syndic_articles', $where,'','',$limite?"0,$limite":'');
	foreach ($articles as $article) {
		if (!mastodon_retrouver_status('', $article['url'], $article['id_syndic_article'])) {
			sql_updateq('spip_syndic_articles', array('statut' => 'refuse'), 'id_syndic_article='.intval($article['id_syndic_article']));
			spip_log('mastodon_update_anciens_articles_syndiques : '.$article['url'].' non trouve -> refuse #'.$article['id_syndic_article'], 'mastodon'._LOG_INFO_IMPORTANTE);
		}
	}

}

/**
 * Rechercher et actualiser un status en base
 * @param string $raw_data
 * @param string $url
 * @param int $id_syndic_article
 * @return array|string
 */
function mastodon_retrouver_status($raw_data, $url, $id_syndic_article) {

	if (!$raw_data) {
		include_spip('inc/mastodon');
		$res = mastodon_api_call("search", "get", array('q' => $url));
		if (count($res['statuses'])
		  and $status = reset($res['statuses'])
		  and $status['uri'] = $url) {
			$raw_data = json_encode($status);
			sql_updateq('spip_syndic_articles', array('raw_data' => $raw_data, 'raw_format' => 'json', 'raw_methode' => 'mastodon'), 'id_syndic_article='.intval($id_syndic_article));
			spip_log("mastodon_retrouver_status : $url OK #$id_syndic_article a jour", 'mastodon');
			return $status;
		}
	}

	return '';
}