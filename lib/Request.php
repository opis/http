<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013-2015 Marius Sarca
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
    /** @var    \Opis\Http\ProxyHandler Proxy */
    protected $proxy;
    
    /** @var    array   Cache   */  
    protected $cache = array();
    
    /** @var    array   Request */
    protected $request = array();
    
    /**
     * Constructor.
     *
     * @param   array   $get        GET data
     * @param   array   $post       POST data
     * @param   array   $cookies    Cookie data
     * @param   array   $files      File data
     * @param   array   $server     Server info
     * @param   string  $body       Request body
     */

    public function __construct(array $get,
                                array $post,
                                array $cookies,
                                array $files,
                                array $server,
                                $body = null)
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
     * Creates a new reequest object using the global variables
     *
     * @param   \Opis\Http\ProxyHandler $proxy  Proxy
     *
     * @return  \Opis\Http\Request
     */
    
    public static function fromGlobals(ProxyHandler $proxy = null)
    {
        $request = new Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, null);
        $request->proxy = $proxy;
        return $request;
    }
    
    
    public static function create($url, $method = 'GET', array $input = array(), array $cookies = array(), array $files = array(), array $server = array(), $body = null)
    {
        $server = array_replace(array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Opis/1.X',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ), $server);
        
        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);
        
        $components = parse_url($url);
        
        if (isset($components['host']))
        {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }
        
        if (isset($components['scheme']))
        {
            if ('https' === $components['scheme'])
            {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            }
            else
            {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }
        
        
        if (isset($components['port']))
        {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] = $server['HTTP_HOST'].':'.$components['port'];
        }
        
        if (isset($components['user']))
        {
            $server['PHP_AUTH_USER'] = $components['user'];
        }
        
        if (isset($components['pass']))
        {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }
        
        if (!isset($components['path']))
        {
            $components['path'] = '/';
        }
        
        switch (strtoupper($method))
        {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE']))
                {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
            case 'PATCH':
                $post = $input;
                $get = array();
                break;
            default:
                $post = array();
                $get = $input;
                break;
        }
        
        $queryString = '';
        
        if (isset($components['query']))
        {
            parse_str(html_entity_decode($components['query']), $qs);
            if ($get)
            {
                $get = array_replace($qs, $get);
                $queryString = http_build_query($get, '', '&');
            }
            else
            {
                $get = $qs;
                $queryString = $components['query'];
            }
        }
        elseif ($get)
        {
            $queryString = http_build_query($get, '', '&');
        }
        
        $server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
        $server['QUERY_STRING'] = $queryString;
        
        return new Request($get, $post, $cookies, $files, $server, $body);
    }
    
    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @author Fabien Potencier <fabien@symfony.com>
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
    
    
    public function response()
    {
        if(!isset($this->cache['response']))
        {
            $this->cache['response'] = new Response($this);
        }
        return $this->cache['response'];
    }
    
    /**
     * Returns the raw request body.
     * 
     * @return  string
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
     * Parse the request body and returns the value on the specified key.
     * 
     * @param   string      $key        Key
     * @param   mixed       $default    Default value
     * 
     * @return  mixed
     */

    protected function getParsed($key, $default)
    {
        if(!isset($this->cache['parsed_body']))
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
            
            $this->cache['parsed_body'] = $parsedBody;
        }
        
        if($key === null)
        {
            return $this->cache['parsed_body'];
        }
        
        return isset($this->cache['parsed_body'][$key]) ? $this->cache['parsed_body'][$key] : $default;
    }
    
    /**
     * Retrieves data from the GET parameters.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function get($key = null, $default = null)
    {
        if($key === null)
        {
            return $this->request['get'];
        }
        
        return isset($this->request['get'][$key]) ? $this->request['get'][$key] : $default;
    }

    /**
     * Retrieves data from the POST parameters.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function post($key = null, $default = null)
    {
        if($key === null)
        {
            return $this->request['post'];
        }
        
        return isset($this->request['post'][$key]) ? $this->request['post'][$key] : $default;
    }

    /**
     * Retrieves data from the PUT parameters.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function put($key = null, $default = null)
    {
        return $this->getParsed($key, $default);
    }

    /**
     * Retrieves data from the PATCH parameters.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function patch($key = null, $default = null)
    {
        return $this->getParsed($key, $default);
    }

    /**
     * Retrieves data from the DELETE parameters.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function delete($key = null, $default = null)
    {
        return $this->getParsed($key, $default);
    }

    /**
     * Retrieves cookie data.
     *
     * @param   string  $name       (optional) Cookie name
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  string
     */

    public function cookie($name = null, $default = null)
    {
        if($name === null)
        {
            return $this->request['cookies'];
        }
        
        return isset($this->request['cookies'][$name]) ? $this->request['cookies'][$name] : $default;
    }
    
    
    /**
     * Retrieves file data.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function file($key = null, $default = null)
    {
        if($key === null)
        {
            return $this->request['files'];
        }
        
        return isset($this->request['files'][$key]) ? $this->request['files'][$key] : $default;
    }

    /**
     * Retrieves server info.
     *
     * @param   string  $key        (optional) Key
     * @param   mixed   $default    (optional) Default value
     * 
     * @return  mixed
     */

    public function server($key = null, $default = null)
    {
        if($key === null)
        {
            return $this->request['server'];
        }
        
        return isset($this->request['server'][$key]) ? $this->request['server'][$key] : $default;
    }

    /**
     * Returns the value of the specified header
     *
     * @param   string  $name       Header's name
     * @param   mixed   $default    Default value
     * 
     * @return mixed
     */

    public function header($name, $default = null)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return isset($this->request['headers'][$name]) ? $this->request['headers'][$name] : $default;
    }
    
    /**
     * Returns the request's URI
     *
     * @return  string 
     */
    
    public function requestUri()
    {
        if(!isset($this->cache['request_uri']))
        {
            $this->cache['request_uri'] = $this->resolveRequestUri();
        }
        
        return $this->cache['request_uri'];
    }
    
    /**
     * Returns the request's base URL
     *
     * @return  string 
     */
    
    public function baseUrl()
    {
        if(!isset($this->cache['base_url']))
        {
            $this->cache['base_url'] = $this->resolveBaseUrl();
        }
        
        return $this->cache['base_url'];
    }
    
    /**
     * Returns the request's base path
     *
     * @return  string 
     */
    
    public function basePath()
    {
        if(!isset($this->cache['base_path']))
        {
            $this->cache['base_path'] = $this->resolveBasePath();
        }
        
        return $this->cache['base_path'];
    }
    
    /**
     * Returns the request's path
     *
     * @return  string 
     */
        
    public function path()
    {
        if(!isset($this->cache['path']))
        {
            $this->cache['path'] = $this->resolvePath();
        }
        
        return $this->cache['path'];
    }
    
    /**
     * Returns the name of the current script
     *
     * @return  string
     */
    
    public function scriptName()
    {
        if(!isset($this->cache['script_name']))
        {
            $this->cache['script_name'] = $this->server('SCRIPT_NAME', $this->server('ORIG_SCRIPT_NAME', ''));
        }
        
        return $this->cache['script_name'];
    }
    
    /**
     * The request's scheme.
     *
     * @return  string
     */
    
    public function scheme()
    {
        if(!isset($this->cache['scheme']))
        {
            $this->cache['scheme'] = $this->isSecure() ? 'https' : 'http';
        }
        
        return $this->cache['scheme'];
    }
    
    /**
     * Returns the port on which the request was made
     *
     * @return  string
     */
    
    public function port()
    {
        if(!isset($this->cache['port']))
        {
            $this->cache['port'] = $this->resolvePort();
        }
        
        return $this->cache['port'];
    }
    
    /**
     * Returns the host name
     *
     * @return  string
     */
    
    public function host()
    {
        if(!isset($this->cache['host']))
        {
            $this->cache['host'] = $this->resolveHost();
        }
        
        return $this->cache['host'];
    }
    
    /**
     * Get the host
     *
     * @return  string
     */
    
    public function httpHost()
    {
        if(!isset($this->cache['http_host']))
        {
            $this->cache['http_host'] = $this->resolveHttpHost();
        }
        
        return $this->cache['http_host'];
    }
    
    /**
     * Get the host and the scheme used for the request
     *
     * @return  string
     */
    
    public function schemeAndHttpHost()
    {
        if(!isset($this->cache['scheme_and_http_host']))
        {
            $this->cache['scheme_and_http_host'] = $this->scheme() .'://' . $this->httpHost();
        }
        
        return  $this->cache['scheme_and_http_host'];
    }
    
    /**
     * Get the full URL
     *
     * return   string
     */
    
    public function url()
    {
        if(!isset($this->cache['url']))
        {
            if(null !== $qs = $this->queryString())
            {
                $qs = '?' . $qs;
            }
            
            $this->cache['url'] = $this->schemeAndHttpHost() . $this->baseUrl() . $this->path() . $qs;
        }
        
        return $this->cache['url'];
    }
    
    public function uriForPath($path)
    {
        return $this->schemeAndHttpHost() . $this->baseUrl() . $path;
    }
    
    public function queryString()
    {
        if(!isset($this->cache['query_string']))
        {
            $qs = self::normalizeQueryString($this->server('QUERY_STRING'));
            $this->cache['query_string'] = ($qs === '') ? null : $qs;
        }
        
        return $this->cache['query_string'];
    }
    
    /**
     * Checks if the request was made using HTTPS
     *
     * @return boolean
     */

    public function isSecure()
    {
        if(!isset($this->cache['is_secure']))
        {
            if($this->proxy !== null && $proto = $this->proxy->getProto($this))
            {
                $this->cache['is_secure'] = in_array(strtolower(current(explode(',', $proto))), array('https', 'on', 'ssl', '1'));
            }
            else
            {
                $https = $this->server('https');
                $this->cache['is_secure'] = strtolower($https) == 'on' || $https == 1;
            }
        }
        
        return $this->cache['is_secure'];
    }
    
    /**
     * Checks if the request was made using AJAX
     *
     * @return boolean
     */

    public function isAjax()
    {
        if(!isset($this->cache['is_ajax']))
        {
            $this->cache['is_ajax'] = $this->header('X-Requested-With') === 'XMLHttpRequest' || $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
        }
        
        return $this->cache['is_ajax'];
    }

    
    /**
     * Returns the request method that was used.
     *
     * @return string
     */

    public function method()
    {
        if(!isset($this->cache['method']))
        {
            $this->cache['method'] = $this->resolveMethod();
        }
        
        return $this->cache['method'];
    }

    /**
     * Returns the real request method that was used.
     *
     * @return string
     */

    public function realMethod()
    {
        if(!isset($this->cache['real_method']))
        {
            $this->cache['real_method'] = strtoupper($this->server('REQUEST_METHOD'), 'GET');
        }
        
        return $this->cache['real_method'];
    }
    
    /**
     * Returns the user's IP addresses
     *
     * @return  array
     */
    
    public function clientIps()
    {
        if(!isset($this->cache['client_ips']))
        {
            $this->cache['client_ips'] = $this->resolveClientIps();
        }
        
        return $this->cache['client_ips'];
    }
    
    /**
     * Returns the IP of the client that made the request.
     *
     * @return string
     */
    
    public function ip()
    {
        if(!isset($this->cache['ip']))
        {
            $this->cache['ip'] = reset($this->clientIps());
        }
        
        return $this->cache['ip'];
    }
    
    
     /**
     * Returns the basic HTTP authentication username or NULL.
     *
     * @return string
     */

    public function username()
    {
        return $this->server('PHP_AUTH_USER');
    }

    /**
     * Returns the basic HTTP authentication password or NULL.
     *
     * @return string
     */

    public function password()
    {
        return $this->server('PHP_AUTH_PW');
    }

    /**
     * Returns the referer.
     *
     * @param   string  $default    (optional) Default value
     * 
     * @return  string
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
            else
            {
                $method = strtoupper(trim($this->post('_method', 'POST')));
                
                if(!in_array($method, array('PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT')))
                {
                    $method = 'POST';
                }
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
        $baseUrl = '';
        $filename = basename($this->server('SCRIPT_FILENAME'));
        
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
                return rtrim($prefix, '/');
            }
        }
        
        $truncatedRequestUri = $requestUri;
        
        if (false !== $pos = strpos($requestUri, '?'))
        {
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
        
        if (DIRECTORY_SEPARATOR === '\\')
        {
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
        
        if ($baseUrl !== null && false === $pathInfo = substr($requestUri, strlen($baseUrl)))
        {
            return '/';
        }
        elseif (null === $baseUrl)
        {
            return $requestUri;
        }
        
        return $pathInfo;
    }
    
    protected function resolveHost()
    {
        if($this->proxy !== null && $host = $this->proxy->getHost($this))
        {
            $host = trim(end(explode(',', $host)));
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
        
        if($this->proxy !== null && !$this->proxy->isTrustedHost($host))
        {
            throw new \UnexpectedValueException(sprintf('Untrusted Host "%s"', $host));
        }
        
        return $host;
    }
    
    protected function resolvePort()
    {
        if($this->proxy !== null)
        {
            if($port = $this->proxy->getPort($this))
            {
                return $port;
            }
            
            if('https' === $this->proxy->getProto($this, 'http'))
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
            
            return 'https' === $this->scheme() ? 443 : 80;
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
        if($this->proxy === null || $this->proxy->getIp($this) === null)
        {
            return array($ip);
        }
        
        $clientIps = array_map('trim', explode(',', $this->proxy->getIp($this)));
        $clientIps[] = $ip;
        
        $trustedProxies = $this->proxy->getProxies();
        
        if(empty($trustedProxies))
        {
            $trustedProxies = array($ip);
        }
        
        $ip = $clientIps[0];
        
        foreach ($clientIps as $key => $clientIp)
        {
            if ($this->proxy->checkIp($clientIp, $trustedProxies))
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
