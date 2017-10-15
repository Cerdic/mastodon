<?php

/**
 * HttpRequest
 * 
 * @author Maxence Cauderlier
 * @link http://max-koder.fr
 * @link https://framagit.org/MaxKoder/TootoPHP
 * @link https://git.nursit.net/Cerdic/TootoPHP
 * @package TootoPHP
 * @version 1.3.0
 * @license http://opensource.org/licenses/MIT The MIT License  
 */

namespace TootoPHP;

/**
 * HttpRequest is a way to do http requests and get responses
 */

class HttpRequest
{

    /**
     * Mastodon API URL
     * @var string
     */
    public $apiURL;
    
    /**
     * Mastodon Instance URL
     * @var string
     */
    public $domainURL;

    /**
     * Last HTTP Response code
     * @var int
     */
    public $last_http_code;

    /**
     * Last URL requested
     * @var string
     */
    public $last_url;

    /**
     * Last headers recieved
     * @var string
     */
    public $last_headers;


    /**
     * Setting up domain and API URL
     * 
     * @param string $domain
     */
    public function __construct($domain)
    {
        $this->domainURL = 'https://' . $domain . '/';
        $this->apiURL = 'api/v1/';
    }

    /**
     * Do a POST http request and return response.
     * 
     * For URL, domain will be automatically added.
     * Array headers and params will be automatically encoded for request.
     * 
     * If response is JSON format, it will be decoded before return
     * 
     * @param string $url
     * @param array $headers
     * @param array $params
     * @return mixed
     */
    public function post($url, $headers = [], $params = [])
    {
        return $this->request(
            'POST', 
            $url, 
            $headers, 
            $params
        );
    }
    
    /**
     * Do a GET http request and return response.
     * 
     * For URL, domain will be automatically added.
     * Array headers and params will be automatically encoded for request.
     * 
     * If response is JSON format, it will be decoded before return
     * 
     * @param string $url
     * @param array $headers
     * @param array $params
     * @return mixed
     */
    public function get($url, $headers = [],$params = [])
    {
        return $this->request(
            'GET', 
            $url, 
            $headers,
            $params
        );
    }

    /**
     * Do a http request and return response.
     * 
     * For URL, domain will be automatically added.
     * Array headers and params will be automatically encoded for request.
     * 
     * If response is JSON format, it will be decoded before return
     *
     * @throws \Exception
     * 
     * @param string $method POST or GET
     * @param string $url
     * @param array $headers
     * @param array $params
     * @return mixed
     */
    protected function request($method, $url, $headers = [], $params = [])
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, $this->getOpts($method, $url, $headers, $params));

        // on veut recuperer les headers de la reponse
        curl_setopt ($curl, CURLOPT_HEADER, 1);

        $response = curl_exec($curl);

        if (curl_error($curl) !== '') {
	        throw new \Exception('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        }
        $infos = curl_getinfo($curl);


        $headers = substr($response, 0, $infos['header_size']);
        $response = substr($response, $infos['header_size']);

        $this->last_http_code = $infos['http_code'];
        $this->last_url = $infos['url'];
        $this->last_headers = $headers;
        curl_close($curl);
        
        if ($response !==  false) {
            $json = json_decode($response, true);
        }
        else {
            return false;
        }
        
        return ($json !== null) ? $json : $response ;
    }

    /**
     * Encode Parameters before HTTP request
     * 
     * @param array $params
     * @return string
     */
    protected function encodeParameters($params)
    {
        if (is_array($params) && count($params) > 0) {
            // Many parameters, encode them
            $paramsString = '';
            foreach ($params as $key => $value) {
                $paramsString .= '&' . urlencode($key) . '=' . urlencode($value);
            }
            // Remove first '&'
            return substr($paramsString, 1);
        } elseif ($params) {
            // return original
            return $params;
        }
    }

    /**
     * Encode Headers before HTTP request
     * 
     * @param array $headers
     * @return string
     */
    protected function encodeHeaders($headers)
    {
        if (is_array($headers) && count($headers) > 0) {
            // Many headers, encode them
            $headersString = '';
            foreach ($headers as $key => $value) {
                $headersString .= "{$key}: {$value}\r\n";
            }
            // Return trimmed string
            return trim($headersString);
        }
        return null;
    }

    /**
     * Get Options to create the cURL Opts
     * 
     * @param string $method POST or GET
     * @param string $url    URL
     * @param array $headers
     * @param array $params
     * @return array
     */
    protected function getOpts($method, $url, $headers, $params)
    {
        if (isset($headers['Authorization'])) {
            $params['access_token'] = str_replace('Bearer ', '', $headers['Authorization']);
        }
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . $this->encodeParameters($params);
            $opts = [];
        }
        else {
            $opts = [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $params
            ];
        }
        $defaultsOpts = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_URL            => $this->domainURL . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 30
        ];

        return $defaultsOpts + $opts;
    }

}
