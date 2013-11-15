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
    
    /** @var    array   GET data */
    protected $get;

    /** @var    array   POST data */
    protected $post;

    /** @var    array   COOKIE data */
    protected $cookies;

    /** @var    array   FILE data */
    protected $files;

    /** @var    array   Server info */
    protected $server;

    /** @var    string  Raw request body. */
    protected $body;

    /** @var    array   Parsed request body. */
    protected $parsedBody;

    /** @var    array   Request headers. */
    protected $headers = array();
    
    /** @var    string  Language. */
    protected $language;
    
    /** @var string Ip address of the client that made the request. */
    protected $ip = '127.0.0.1';

    /** @var    boolean Is this an Ajax request? */
    protected $isAjax;

    /** @var    boolean Was the request made using HTTPS? */
    protected $isSecure;
    
    /** @var    string  Holds the request path. */
    protected $path;
    
    /** @var    string  Holds the real request path */
    protected $realPath;

    /** @var    string  Which request method was used? */
    protected $method;

    /** @var    string  The actual request method that was used. */
    protected $realMethod;

    
    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $path       (optional) Request path
     * @param   string  $method     (optional) Request method
     * @param   array   $get        (optional) GET data
     * @param   array   $post       (optional) POST data
     * @param   array   $cookies    (optional) Cookie data
     * @param   array   $files      (optional) File data
     * @param   array   $server     (optional) Server info
     * @param   array   $lang       (optional) Languages
     * @param   string  $body       (optional) Request body
     */

    public function __construct($path = null, $method = null, array $get = array(), array $post = array(),
                                array $cookies = array(), array $files = array(), array $server = array(),
                                $body = null)
    {
        
        $this->get = $get ?: $_GET;
        $this->post = $post ?: $_POST;
        $this->cookies = $cookies ?: $_COOKIE;
        $this->server = $server ?: $_SERVER;
        $this->files = $files ?: $_FILES;
        $this->body = $body;
        
        $this->headers = $this->collectHeaders();
        $this->collectRequestInfo();
        $this->path = empty($path) ? $this->findRequestPath() : $path;
        $this->method = empty($method) ? $this->findRequestMethod() : strtoupper($method);
        
    }
    
    /**
     * Find the request path
     *
     * @access protected
     * @return string
     */
    
    protected function findRequestPath()
    {
        $path = '/';
        if(isset($this->server['PATH_INFO']))
        {
            $path = $this->server['PATH_INFO'];
        }elseif(isset($this->server['REQUEST_URI']))
        {
            if($path = parse_url($this->server['REQUEST_URI'], PHP_URL_PATH))
            {
                $basePath = pathinfo($this->server['SCRIPT_NAME'], PATHINFO_DIRNAME);
                if(stripos($path, $basePath) === 0)
                {
                    $path = mb_substr($path, mb_strlen($basePath));
                }
                $path = rawurlencode($path);
            }
        }
        return $path;
    }
    
    /**
     * Find the request method
     *
     * @access protected
     * @return string
     */
    
    protected function findRequestMethod()
    {
        $method = 'GET';
        if(isset($this->server['REQUEST_METHOD']))
        {
            $method = strtoupper($this->server['REQUEST_METHOD']);
        }
        
        if($method === 'POST')
        {
            if(isset($this->post['REQUEST_METHOD_OVERRIDE']))
            {
                $method = $this->post['REQUEST_METHOD_OVERRIDE'];
            }elseif(isset($this->server['HTTP_X_HTTP_METHOD_OVERRIDE']))
            {
                $method = $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'];
            }
        }
        return strtoupper($method);
    }
    
    
    /**
     * Returns all the request headers.
     *
     * @access protected
     * @return array
     */

    protected function collectHeaders()
    {
        
        $headers = array();
        
        foreach($this->server as $key => $value)
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

    /**
     * Collects information about the request.
     *
     * @access protected
     */

    protected function collectRequestInfo()
    {
        // Get the IP address of the client that made the request
        $checkList = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach($checkList as $check)
        {
            if(isset($this->server[$check]))
            {
                $ip = $this->server[$check];
                if($check == 'HTTP_X_FORWARDED_FOR')
                {
                    $ip = array_pop(explode(',', $ip));
                }
                break;
            }
        }
        
        if(isset($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false)
        {
            $this->ip = $ip;
        }
        
        // Is this an Ajax request?

        $this->isAjax = (isset($this->server['HTTP_X_REQUESTED_WITH']) && ($this->server['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));

        // Was the request made using HTTPS?

        $this->isSecure = (isset($this->server['HTTPS']) && filter_var($this->server['HTTPS'], FILTER_VALIDATE_BOOLEAN));

        // Get the real request method that was used

        $this->realMethod = isset($this->server['REQUEST_METHOD']) ? strtoupper($this->server['REQUEST_METHOD']) : 'GET';
    }
    
    /**
     * Remove language prefix from path
     *
     * @param   array   $languages  Supported languages eg. array('en', 'fr', ..)
     * @param   string  $default    Default language
     */
    
    public function removePrefix(array $languages, $default = '')
    {
        if($this->path !== '/')
        {
            foreach($languages as $language)
            {
                if($this->path === '/' . $language || strpos($this->path, '/' . $language . '/') === 0)
                {
                    $this->language = $language;
                    $this->path = '/' . ltrim(mb_substr($this->path, (mb_strlen($language) + 1)), '/');
                    return;
                }
            }
        }
        $this->language = $default;
    }
    

    /**
     * Returns the raw request body.
     *
     * @access public
     * @return string
     */

    public function body()
    {
        if($this->body === null)
        {
            $this->body = file_get_contents('php://input');
        }
        return $this->body;
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
        if($this->parsedBody === null)
        {
            switch($this->header('content-type'))
            {
                case 'application/x-www-form-urlencoded':
                    parse_str($this->body(), $this->parsedBody);
                    break;
                case 'text/json':
                case 'application/json':
                    $this->parsedBody = json_decode($this->body(), true);
                    break;
                default:
                    $this->parsedBody = array();
            }
        }
        return ($key === null) ? $this->parsedBody : isset($this->parsedBody[$key]) ? $this->parsedBody[$key] : $default;
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
        return ($key === null) ? $this->get : isset($this->get[$key]) ? $this->get[$key] : $default;
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
        return ($key === null) ? $this->post : isset($this->post[$key]) ? $this->post[$key] : $default;
    }

    /**
     * Fetch data from the PUT parameters.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function put($key = null, $default = null) {
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
        return ($name === null) ? $this->cookies : isset($this->cookies[$name]) ? $this->cookies[$name] : $default;
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
        return ($key === null) ? $this->files : isset($this->files[$key]) ? $this->files[$key] : $default;
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
        return ($key === null) ? $this->server : isset($this->server[$key]) ? $this->server[$key] : $default;
    }

    /**
     * Checks if the keys exist in the data of the current request method.
     *
     * @access public
     * @param string $key Array key
     * @param string $method (optional) Request method
     * @return boolean
     */

    public function has($key, $method = null)
    {
        $method = $method ?: strtolower($this->realMethod);
        $array = $this->method();
        return isset($array[$key]);
    }
    
    
    /**
     * Fetch data the current request method.
     *
     * @access public
     * @param string $key (optional) Array key
     * @param mixed $default (optional) Default value
     * @return mixed
     */

    public function data($key = null, $default = null)
    {
        $method = strtolower($this->realMethod);
        return $this->$method($key, $default);
    }
    
    /**
     * Returns request data where keys not in the whitelist have been removed.
     *
     * @access public
     * @param array $keys Keys to whitelist
     * @param array $defaults (optional) Default values
     * @return array
     */

    public function whitelisted(array $keys, array $defaults = array())
    {
        return array_intersect_key($this->data(), array_flip($keys)) + $defaults;
    }
    
    /**
     * Returns request data where keys in the blacklist have been removed.
     *
     * @access public
     * @param array $keys Keys to whitelist
     * @param array $defaults (optional) Default values
     * @return array
     */

    public function blacklisted(array $keys, array $defaults = array())
    {
        return array_diff_key($this->data(), array_flip($keys)) + $defaults;
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
        return isset($this->headers[$name]) ? $this->headers[$name] : $default;
    }
    
    /**
     * Returns the ip of the client that made the request.
     *
     * @access public
     * @return string
     */
    
    public function ip()
    {
        return $this->ip;
    }

    /**
     * Returns TRUE if the request was made using Ajax and FALSE if not.
     *
     * @access public
     * @return boolean
     */

    public function isAjax()
    {
        return $this->isAjax;
    }

    /**
     * Returns TRUE if the request was made using HTTPS and FALSE if not.
     *
     * @access public
     * @return boolean
     */

    public function isSecure()
    {
        return $this->isSecure;
    }

    /**
     * Returns the request path.
     *
     * @access public
     * @return string
     */

    public function path()
    {
        return $this->path;
    }
    
    /**
     * Returns the request method that was used.
     *
     * @access public
     * @return string
     */

    public function method()
    {
        return $this->method;
    }

    /**
     * Returns the real request method that was used.
     *
     * @access public
     * @return string
     */

    public function realMethod()
    {
        return $this->realMethod;
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
}