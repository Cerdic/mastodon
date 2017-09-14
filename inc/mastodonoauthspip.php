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

include_spip('inc/mastodonoauth');

/**
 * Mastodon OAuth class
 */
class MastodonOAuthSPIP extends MastodonOAuth {

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $postfields = NULL) {
	  // var_dump(parent::http($url, $method, $postfields));
	  include_spip("inc/distant");

 		$taille_max = _INC_DISTANT_MAX_SIZE;
	  $trans = false;
	  $refuser_gz = false;
	  $date_verif = '';
	  $uri_referer = '';
	  $datas = '';
	  $boundary = '';
	  $current = $url;

	  switch ($method) {
      case 'POST':
				if (!empty($postfields)) {
				  if (is_string($postfields)){
				    parse_str($postfields,$datas);
				  }
				  else
				    $datas = $postfields;

				  list($type, $postdata) = prepare_donnees_post($datas, $boundary);
				  $datas = $type . 'Content-Length: ' . strlen($postdata) . "\r\n\r\n" . $postdata;
				}
				break;
      case 'DELETE':
       if (!empty($postfields)) {
	       $current = "{$current}?{$postfields}";
       }
		}

	  $this->http_info = array(
		  'url' => $current,
		  'http_code' => 0,
		  'content_type' => '',
		  'header_size' => 0,
		  'request_size' => 0,
		  'redirect_count' => 0,
	  );
	  $this->url = $current;
	  $response = '';

	 	// dix tentatives maximum en cas d'entetes 301...
	 	for ($i = 0; $i<10; $i++){
		  $current = recuperer_lapage($current, $trans, $method, $taille_max, $datas, $refuser_gz, $date_verif, $uri_referer);
	 		if (!$current) break;
	 		if (is_array($current)){
			  break;
	 		}
		  else
			  spip_log("recuperer page recommence sur $current");
	 	}

	  $this->info['redirect_count'] = $i;
	  if (!$current){
		  $this->http_code = 500;
		  $this->info['http_code'] = 500;
		  if ($GLOBALS['meta']["http_proxy"])
		    return $response;
		  else
		    return parent::http($url, $method, $postfields);
	  }
	  if (!is_array($current)){
		  $this->http_code = 301;
		  $this->http_info['http_code'] = 301;
		  return $response;
	  }

	  $this->http_code = 200;
	  $this->http_info['http_code'] = 200;
    list($headers, $response) = $current;

	  $this->http_info['header_size'] = strlen($headers);
	  $this->http_info['request_size'] = strlen($response);


    return $response;
  }

}