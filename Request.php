<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Http;

class Request
{
    const HEADER_CLIENT_IP = 'client_ip';
    const HEADER_CLIENT_HOST = 'client_host';
    const HEADER_CLIENT_PROTO = 'client_proto';
    const HEADER_CLIENT_PORT = 'client_port';
    

    protected static $trustedProxies = array();

    protected static $trustedHostPatterns = array();

    protected static $trustedHosts = array();
    
    /**
     * Names for headers that can be trusted when
     * using trusted proxies.
     *
     * The default names are non-standard, but widely used
     * by popular reverse proxies (like Apache mod_proxy or Amazon EC2).
     */
    
    protected static $trustedHeaders = array(
        self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
        self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
    );

    protected static $httpMethodParameterOverride = false;

    protected $info = array();
    
    protected $request = array();
    
    /**
     * Constructor.
     *
     * @access  public
     * @param   array   $get        GET data
     * @param   array   $post       POST data
     * @param   array   $cookies    Cookie data
     * @param   array   $files      File data
     * @param   array   $server     Server info
     * @param   string  $body       Request body
     */

    public function __construct(array $get, array $post, array $cookies,
                                array $files, array $server, $body = null)
    {
        $this->request = array(
            'get' => $get,
            'post' => $post,
            'cookies' => $cookies,
            'files' => $files,
            'server' => $server,
            'body' => $body,
        );
        
        $this->request['headers'] = $this->resolveHeaders();
    }

    /**
     * Sets a list of trusted proxies.
     *
     * You should only list the reverse proxies that you manage directly.
     *
     * @param array $proxies A list of trusted proxies
     */
    
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
    }

    /**
     * Gets the list of trusted proxies.
     *
     * @return array An array of trusted proxies.
     */
    
    public static function getTrustedProxies()
    {
        return self::$trustedProxies;
    }

    /**
     * Sets a list of trusted host patterns.
     *
     * You should only list the hosts you manage using regexs.
     *
     * @param array $hostPatterns A list of trusted host patterns
     */
    
    public static function setTrustedHosts(array $hostPatterns)
    {
        self::$trustedHostPatterns = array_map(function ($hostPattern) {
            return sprintf('{%s}i', str_replace('}', '\\}', $hostPattern));
        }, $hostPatterns);
        // we need to reset trusted hosts on trusted host patterns change
        self::$trustedHosts = array();
    }

    /**
     * Gets the list of trusted host patterns.
     *
     * @return array An array of trusted host patterns.
     */
    
    public static function getTrustedHosts()
    {
        return self::$trustedHostPatterns;
    }

    /**
     * Sets the name for trusted headers.
     *
     * The following header keys are supported:
     *
     *  * Request::HEADER_CLIENT_IP:    defaults to X-Forwarded-For
     *  * Request::HEADER_CLIENT_HOST:  defaults to X-Forwarded-Host
     *  * Request::HEADER_CLIENT_PORT:  defaults to X-Forwarded-Port
     *  * Request::HEADER_CLIENT_PROTO: defaults to X-Forwarded-Proto
     *
     * Setting an empty value allows to disable the trusted header for the given key.
     *
     * @param string $key   The header key
     * @param string $value The header name
     *
     * @throws \InvalidArgumentException
     */
    
    public static function setTrustedHeaderName($key, $value)
    {
        if (!array_key_exists($key, self::$trustedHeaders))
        {
            throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key));
        }

        self::$trustedHeaders[$key] = $value;
    }

    /**
     * Gets the trusted proxy header name.
     *
     * @param string $key The header key
     *
     * @return string The header name
     *
     * @throws \InvalidArgumentException
     */
    
    public static function getTrustedHeaderName($key)
    {
        if (!array_key_exists($key, self::$trustedHeaders))
        {
            throw new \InvalidArgumentException(sprintf('Unable to get the trusted header name for key "%s".', $key));
        }
        
        return self::$trustedHeaders[$key];
    }
    
    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     *
     * @return string A normalized query string for the Request
     */
        
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs)
        {
            return '';
        }
        
        $parts = array();
        $order = array();
        
        foreach (explode('&', $qs) as $param)
        {
            if ('' === $param || '=' === $param[0])
            {
                // Ignore useless delimiters, e.g. "x=y&".
                // Also ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
                // PHP also does not include them when building _GET.
                continue;
            }
            $keyValuePair = explode('=', $param, 2);
            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str. This is why we use urldecode and then normalize to
            // RFC 3986 with rawurlencode.
            $parts[] = isset($keyValuePair[1]) ?
                rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1])) :
                rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }
        
        array_multisort($order, SORT_ASC, $parts);
        
        return implode('&', $parts);
    }
    
    /**
     * Returns the raw request body.
     *
     * @access public
     * @return string
     */

    public function body()
    {
        if($this->request['body'] === null)
        {
            $this->request['body'] = file_get_contents('php://input');
        }
        return $this->request['body'];
    }
    
    /**
     * Parses the request body and returns the chosen value.
     *
     * @access protected
     * @param string $key Array key
     * @param mixed $default Default value
     * @return mixed
     */

    protected function getParsed($key, $default)
    {
        if(!isset($this->info['parsed_body']))
        {
            $parsedBody = array();
            switch($this->header('content-type'))
            {
                case 'application/x-www-form-urlencoded':
                    parse_str($this->body(), $parsedBody);
                    break;
                case 'text/json':
                case 'application/json':
                case 'application/x-json':
                    $parsedBody = json_decode($this->body(), true);
                    break;
            }
            $this->info['parsed_body'] = $parsedBody;
        }
        
        return ($key === null) ? $this->info['parsed_body']: isset($this->info['parsed_body'][$key]) ? $this->info['parsed_body'][$key] : $default;
    }
    
    /**
     * Fetch data from the GET parameters.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function get($key = null, $default = null)
    {
        return ($key === null) ? $this->request['get'] : isset($this->request['get'][$key]) ? $this->request['get'][$key] : $default;
    }

    /**
     * Fetch data from the POST parameters.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function post($key = null, $default = null)
    {
        return ($key === null) ? $this->request['post'] : isset($this->request['post'][$key]) ? $this->request['post'][$key] : $default;
    }

    /**
     * Fetch data from the PUT parameters.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function put($key = null, $default = null)
    {
        return $this->getParsed($key, $default);
    }

    /**
     * Fetch data from the PATCH parameters.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function patch($key = null, $default = null)
    {
        return $this->getParsed($key, $default);
    }

    /**
     * Fetch data from the DELETE parameters.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function delete($key = null, $default = null)
    {
        return $this->getParsed($key, $default);
    }

    /**
     * Fetch signed cookie data.
     *
     * @access public
     * @param string $name (optional) Cookie name
     * @param mixed $default (optional) Default value
     * @return string
     */

    public function cookie($name = null, $default = null)
    {
        return ($name === null) ? $this->request['cookies'] : isset($this->request['cookies'][$name]) ? $this->request['cookies'][$name] : $default;
    }
    
    
    /**
     * Fetch file data.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function file($key = null, $default = null)
    {
        return ($key === null) ? $this->request['files'] : isset($this->request['files'][$key]) ? $this->request['files'][$key] : $default;
    }

    /**
     * Fetch server info.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function server($key = null, $default = null)
    {
        return ($key === null) ? $this->request['server'] : isset($this->request['server'][$key]) ? $this->request['server'][$key] : $default;
    }

    /**
     * Returns a request header.
     *
     * @access public
     * @param string $name Header name
     * @param mixed $default Default value
     * @return mixed
     */

    public function header($name, $default = null)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return isset($this->request['headers'][$name]) ? $this->request['headers'][$name] : $default;
    }
    
    
    public function requestUri()
    {
        if(!isset($this->info['request_uri']))
        {
            $this->info['request_uri'] = $this->resolveRequestUri();
        }
        
        return $this->info['request_uri'];
    }
    
    public function baseUrl()
    {
        if(!isset($this->info['base_url']))
        {
            $this->info['base_url'] = $this->resolveBaseUrl();
        }
        
        return $this->info['base_url'];
    }
    
    
    public function basePath()
    {
        if(!isset($this->info['base_path']))
        {
            $this->info['base_path'] = $this->resolveBasePath();
        }
        
        return $this->info['base_path'];
    }
    
    
    public function path()
    {
        if(!isset($this->info['path']))
        {
            $this->info['path'] = $this->resolvePath();
        }
        
        return $this->info['path'];
    }
    
    /**
     * Returns current script name.
     *
     * @access public
     * @return string
     */
    
    public function scriptName()
    {
        if(!isset($this->info['script_name']))
        {
            $this->info['script_name'] = $this->server('SCRIPT_NAME', $this->server('ORIG_SCRIPT_NAME', ''));
        }
        return $this->info['script_name'];
    }
    
    /**
     * The request's scheme.
     *
     * @access public
     * @return string
     */
    public function scheme()
    {
        if(!isset($this->info['scheme']))
        {
            $this->info['scheme'] = $this->isSecure() ? 'https' : 'http';
        }
        
        return $this->info['scheme'];
    }
    
    public function port()
    {
        if(!isset($this->info['port']))
        {
            $this->info['port'] = $this->resolvePort();
        }
        
        return $this->info['port'];
    }
    
    public function host()
    {
        if(!isset($this->info['host']))
        {
            $this->info['host'] = $this->resolveHost();
        }
        
        return $this->info['host'];
    }
    
    public function httpHost()
    {
        if(!isset($this->info['http_host']))
        {
            $this->info['http_host'] = $this->resolveHttpHost();
        }
        
        return $this->info['http_host'];
    }
    
    public function schemeAndHttpHost()
    {
        if(!isset($this->info['scheme_and_http_host']))
        {
            $this->info['scheme_and_http_host'] = $this->scheme() .'://' . $this->httpHost();
        }
        
        return  $this->info['scheme_and_http_host'];
    }
    
    public function uri()
    {
        if(!isset($this->info['uri']))
        {
            if(null !== $qs = $this->queryString())
            {
                $qs .= '?' . $qs;
            }
            $this->info['uri'] = $this->schemeAndHttpHost() . $this->baseUrl() . $this->path() . $qs;
        }
        
        return $this->info['uri'];
    }
    
    public function uriForPath($path)
    {
        return $this->schemeAndHttpHost() . $this->baseUrl() . $path;
    }
    
    public function queryString()
    {
        if(!isset($this->info['query_string']))
        {
            $qs = self::normalizeQueryString($this->server('QUERY_STRING'));
            $this->info['query_string'] = ($qs === '') ? null : $qs;
        }
        
        return $this->info['query_string'];
    }
    
    /**
     * Returns TRUE if the request was made using HTTPS and FALSE if not.
     *
     * @access public
     * @return boolean
     */

    public function isSecure()
    {
        if(!isset($this->info['is_secure']))
        {
            if (self::$trustedProxies && self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && $proto = $this->header(self::$trustedHeaders[self::HEADER_CLIENT_PROTO]))
            {
                $this->info['is_secure'] = in_array(strtolower(current(explode(',', $proto))), array('https', 'on', 'ssl', '1'));
            }
            else
            {
                $https = $this->server('https');
                $this->info['is_secure'] = strtolower($https) == 'on' || $https == 1;
            }
        }
        
        return $this->info['is_secure'];
    }
    
    /**
     * Returns TRUE if the request was made using Ajax and FALSE if not.
     *
     * @access public
     * @return boolean
     */

    public function isAjax()
    {
        if(!isset($this->info['is_ajax']))
        {
            $this->info['is_ajax'] = $this->header('X-Requested-With') === 'XMLHttpRequest' || $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
        }
        
        return $this->info['is_ajax'];
    }

    
    /**
     * Returns the request method that was used.
     *
     * @access public
     * @return string
     */

    public function method()
    {
        if(!isset($this->info['method']))
        {
            $this->info['method'] = $this->resolveMethod();
        }
        
        return $this->info['method'];
    }

    /**
     * Returns the real request method that was used.
     *
     * @access public
     * @return string
     */

    public function realMethod()
    {
        if(!isset($this->info['real_method']))
        {
            $this->info['real_method'] = strtoupper($this->server('REQUEST_METHOD'), 'GET');
        }
        
        return $this->info['real_method'];
    }
    
    public function clientIps()
    {
        if(!isset($this->info['client_ips']))
        {
            $this->info['client_ips'] = $this->resolveClientIps();
        }
        
        return $this->info['client_ips'];
    }
    
    /**
     * Returns the ip of the client that made the request.
     *
     * @access public
     * @return string
     */
    
    public function ip()
    {
        if(!isset($this->info['ip']))
        {
            $this->info['ip'] = reset($this->clientIps());
        }
        
        return $this->info['ip'];
    }
    
    
     /**
     * Returns the basic HTTP authentication username or NULL.
     *
     * @access public
     * @return string
     */

    public function username()
    {
        return $this->server('PHP_AUTH_USER');
    }

    /**
     * Returns the basic HTTP authentication password or NULL.
     *
     * @access public
     * @return string
     */

    public function password()
    {
        return $this->server('PHP_AUTH_PW');
    }

    /**
     * Returns the referer.
     *
     * @access public
     * @param string $default (optional) Value to return if no referer is set
     * @return string
     */

    public function referer($default = '')
    {
        return $this->header('referer', $default);
    }
    
    
    
    protected function resolveHeaders()
    {
        $headers = array();
        
        foreach($this->request['server'] as $key => $value)
        {
            if(strpos($key, 'HTTP_') === 0)
            {
                $headers[substr($key, 5)] = $value;
            }
            elseif(in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE')))
            {
                $headers[$key] = $value;
            }
        }
        
        return $headers;
    }
    
    protected function resolveMethod()
    {
        $method = strtoupper($this->server('REQUEST_METHOD', 'GET'));
        if($method === 'POST')
        {
            if($this->post('REQUEST_METHOD_OVERRIDE') !== null)
            {
                $method = $this->post('REQUEST_METHOD_OVERRIDE');
            }
            elseif($this->server('HTTP_X_HTTP_METHOD_OVERRIDE') !== null)
            {
                $method = $this->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }
        }
        return strtoupper($method);
    }
    
    
    protected function resolveRequestUri()
    {
        $requestUri = '';
        $remove = array();
        
        if($this->header('X_ORIGINAL_URL') !== null)
        {
            $requestUri = $this->header('X_ORIGINAL_URL');
            $remove = array('headers' => 'X_ORIGINAL_URL',
                            'server' => 'HTTP_X_ORIGINAL_URL',
                            'server' => 'UNENCODED_URL',
                            'server' => 'IIS_WasUrlRewritten');
        }
        elseif($this->header('X_REWRITE_URL') !== null)
        {
            $requestUri = $this->header('X_REWRITE_URL');
            $remove = array('headers' => 'X_REWRITE_URL');
        }
        elseif($this->server('IIS_WasUrlRewritten') == '1' && $this->server('UNENCODED_URL') != '')
        {
            $requestUri = $this->server('UNENCODED_URL');
            $remove = array('server' => 'UNENCODED_URL',
                            'server' => 'IIS_WasUrlRewritten');
        }
        elseif($this->server('REQUEST_URI') !== null)
        {
            $requestUri = $this->server('REQUEST_URI');
            $schemeAndHttpHost = $this->schemeAndHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0)
            {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        }
        elseif($this->server('ORIG_PATH_INFO') !== null)
        {
            $requestUri = $this->server('ORIG_PATH_INFO');
            if('' != $query = $this->server('QUERY_STRING', ''))
            {
                $requestUri .= '?' . $query;
            }
            $remove = array('server' => 'ORIG_PATH_INFO');
        }
        
        foreach($remove as $key => $value)
        {
            unset($this->request[$key][$value]);
        }
        
        unset($remove);
        
        $this->request['server']['REQUEST_URI'] = $requestUri;
        
        return $requestUri;
    }
    
    protected function resolveBaseUrl()
    {
        $filename = basename($this->server('SCRIPT_FILENAME'));
        $baseUrl = '';
        if(basename($this->server('SCRIPT_NAME')) === $filename)
        {
            $baseUrl = $this->server('SCRIPT_NAME');
        }
        elseif(basename($this->server('PHP_SELF')) === $filename)
        {
            $baseUrl = $this->server('PHP_SELF');
        }
        elseif(basename($this->server('ORIG_SCRIPT_NAME')) === $filename)
        {
            $baseUrl = $this->server('ORIG_SCRIPT_NAME');
        }
        else
        {
            $file = $this->server('SCRIPT_FILENAME', '');
            $path = $this->server('PHP_SELF', '');
            $pos = strpos($path, '/', 1);
            if($pos !== false)
            {
                $seg = substr($path, 0, $pos + 1);
                $pos = strpos($file, $seg);
                if($pos !== false)
                {
                    $baseUrl = substr($file, $pos);
                }
            }
        }
        
        $requestUri = $this->requestUri();
        
        if($baseUrl)
        {
            if(false !== $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl))
            {
                return $prefix;
            }
            
            if(false !== $prefix = $this->getUrlencodedPrefix($requestUri, dirname($baseUrl)))
            {
                return $prefix;
            }
        }
        
        $truncatedRequestUri = $requestUri;
        if (false !== $pos = strpos($requestUri, '?')) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }
        
        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename))
        {
            return '';
        }
        
        if (strlen($requestUri) >= strlen($baseUrl) && (false !== $pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }
        
        return rtrim($baseUrl, '/');
    }
    
    
    protected function resolveBasePath()
    {
        $baseUrl = $this->baseUrl();
        
        if(empty($baseUrl))
        {
            return '/';
        }
        
        $basePath = $baseUrl;
        
        if(basename($baseUrl) === basename($this->server('SCRIPT_FILENAME')))
        {
            $basePath = dirname($baseUrl);
        }
        
        if (DIRECTORY_SEPARATOR === '\\') {
            $basePath = str_replace('\\', '/', $basePath);
        }
        
        return rtrim($basePath, '/');
    }
    
    protected function resolvePath()
    {
        $requestUri = $this->requestUri();
        
        if($requestUri === null)
        {
            return '/';
        }
        
        $baseUrl = $this->baseUrl();
        $pathInfo = '/';
        
        if ($pos = strpos($requestUri, '?'))
        {
            $requestUri = substr($requestUri, 0, $pos);
        }
        
        if (null !== $baseUrl && false === $pathInfo = substr($requestUri, strlen($baseUrl)))
        {
            return '/';
        }
        elseif (null === $baseUrl)
        {
            return $requestUri;
        }
        
        return (string) $pathInfo;
    }
    
    protected function resolveHost()
    {
        if(self::$trustedProxies && self::$trustedHeaders[self::HEADER_CLIENT_HOST] && $host = $this->header(self::$trustedHeaders[self::HEADER_CLIENT_HOST]))
        {
            $host = end(explode(',', $host));
        }
        elseif(!$host = $this->header('HOST'))
        {
            if(!$host = $this->server('SERVER_NAME'))
            {
                $host = $this->server('SERVER_ADDR', '');
            }
        }
        
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        
        if ($host && !preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host))
        {
            throw new \UnexpectedValueException(sprintf('Invalid Host "%s"', $host));
        }
        
        if(count(self::$trustedHostPatterns) > 0)
        {
            if(in_array($host, self::$trustedHosts))
            {
                return $host;
            }
            
            foreach (self::$trustedHostPatterns as $pattern)
            {
                if (preg_match($pattern, $host))
                {
                    self::$trustedHosts[] = $host;
                    return $host;
                }
            }
            
            throw new \UnexpectedValueException(sprintf('Untrusted Host "%s"', $host));
        }
        
        return $host;
    }
    
    protected function resolvePort()
    {
        if(self::$trustedProxies)
        {
            if(self::$trustedHeaders[self::HEADER_CLIENT_PORT] && $port = $this->header(self::$trustedHeaders[self::HEADER_CLIENT_PORT]))
            {
                return $port;
            }
            
            if(self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && 'https' === $this->header(self::HEADER_CLIENT_PROTO, 'http'))
            {
                return 443;
            }
        }
        
        if($host = $this->header('HOST'))
        {
            if (false !== $pos = strrpos($host, ':'))
            {
                return intval(substr($host, $pos + 1));
            }
            
            return 'https' === $this->getScheme() ? 443 : 80;
        }
        
        return $this->server('SERVER_PORT');
    }
    
    protected function resolveHttpHost()
    {
        $scheme = $this->scheme();
        $port = $this->port();
        if(($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443))
        {
            return $this->host();
        }
        return $this->host() . ':' . $port;
    }
    
    private function resolveClientIps()
    {
        $ip = $this->server('REMOTE_ADDR');
        if(!self::$trustedProxies)
        {
            return array($ip);
        }
        
        if (!self::$trustedHeaders[self::HEADER_CLIENT_IP] || $this->header(self::$trustedHeaders[self::HEADER_CLIENT_IP]) === null)
        {
            return array($ip);
        }
        
        $clientIps = array_map('trim', explode(',', $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_IP])));
        $clientIps[] = $ip;
        
        $trustedProxies = !self::$trustedProxies ? array($ip) : self::$trustedProxies;
        $ip = $clientIps[0];
        
        foreach ($clientIps as $key => $clientIp)
        {
            if (IpUtils::checkIp($clientIp, $trustedProxies))
            {
                unset($clientIps[$key]);
            }
        }
        
        return $clientIps ? array_reverse($clientIps) : array($ip);
        
    }
    
    private function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix))
        {
            return false;
        }
        
        $len = strlen($prefix);
        
        if (preg_match("#^(%[[:xdigit:]]{2}|.){{$len}}#", $string, $match))
        {
            return $match[0];
        }
        
        return false;
    }

}