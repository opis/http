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

class Response
{
    /** @var mixed Response body. */
    protected $body = '';
    
    /** @var string Response content type. */
    protected $contentType = 'text/html';
    
    /** @var string Response charset. */
    protected $charset = 'UTF-8';
    
    /** @var integer Status code. */
    protected $statusCode = 200;
    
    /** @var array Response headers. */
    protected $headers = [];

    /** @var array Header map */
    protected $map = [];
    
    /** @var array Cookies. */
    protected $cookies = [];
    
    /** @var array HTTP status codes. */
    protected $statusCodes = [
        // 1xx Informational
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '102' => 'Processing',
        // 2xx Success
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '207' => 'Multi-Status',
        // 3xx Redirection
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        //'306' => 'Switch Proxy',
        '307' => 'Temporary Redirect',
        // 4xx Client Error
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '418' => 'I\'m a teapot',
        '421' => 'There are too many connections from your internet address',
        '422' => 'Unprocessable Entity',
        '423' => 'Locked',
        '424' => 'Failed Dependency',
        '425' => 'Unordered Collection',
        '426' => 'Upgrade Required',
        '449' => 'Retry With',
        '450' => 'Blocked by Windows Parental Controls',
        // 5xx Server Error
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
        '506' => 'Variant Also Negotiates',
        '507' => 'Insufficient Storage',
        '509' => 'Bandwidth Limit Exceeded',
        '510' => 'Not Extended',
        '530' => 'User access denied',
    ];

    /**
     * Response constructor.
     *
     * @param mixed|null $content
     */
    public function __construct($content = null)
    {
        $this->setBody($content);
    }

    /**
     * Set the response body
     *
     * @param mixed $body
     * @return $this
     */
    public function setBody($body)
    {
        if(!($body instanceof \Closure) &&
           (is_object($body) && !method_exists($body, '__toString'))) {
            throw new \UnexpectedValueException(sprintf("Invalid body type %s", gettype($body)));
        }
        
        $this->body = $body;
        return $this;
    }

    /**
     * Get the response body
     *
     * @return mixed|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the response's content type
     *
     * @param string $contentType
     * @param string|null $charset
     * @return $this
     */
    public function setContentType(string $contentType, string $charset = null)
    {
        $this->contentType = $contentType;
        
        if($charset !== null) {
            $this->charset = $charset;
        }
        
        return $this;
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Set the response's charset
     *
     * @param string $charset
     * @return $this
     */
    public function setCharset(string $charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Get charset
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Set the status code of the response
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode)
    {
        if(isset($this->statusCodes[$statusCode])) {
            $this->statusCode = $statusCode;
        }
        
        return $this;
    }

    /**
     * Get the message associated with current status code
     *
     * @return string
     */
    public function getStatusCodeMessage(): string
    {
        return $this->statusCodes[$this->statusCode] ?? '';
    }

    /**
     * Get status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Add a new header
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addHeader(string $name, string $value)
    {
        $header = strtolower($name);
        $this->headers[$name] = $value;
        $this->map[$header] = $name;
        return $this;
    }

    /**
     * Get the value of a header
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getHeader(string $name, string $default = ''): string
    {
        $header = strtolower($name);

        if(isset($this->map[$header])){
            return $this->headers[$this->map[$header]];
        }

        return $default;
    }

    /**
     * Check if header exists
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->map[strtolower($name)]);
    }

    /**
     * Delete a header
     *
     * @param string $name
     * @return $this
     */
    public function deleteHeader(string $name)
    {
        $header = strtolower($name);

        if(isset($this->map[$header])){
            unset($this->headers[$this->map[$header]]);
            unset($this->map[$header]);
        }

        return $this;
    }

    /**
     * Add headers
     *
     * @param string[] $headers
     * @return $this
     */
    public function addHeaders(array $headers)
    {
        foreach($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
        
        return $this;
    }

    /**
     * Get headers
     *
     * @param string[]|null $filter
     * @return string[]
     */
    public function getHeaders(array $filter = null): array
    {
        if($filter === null){
            return $this->headers;
        }

        $headers = [];

        foreach ($filter as $header){
            $header = strtolower($header);
            if(isset($this->map[$header])){
                $headers[$this->map[$header]] = true;
            }
        }

        return array_intersect_key($headers, $this->headers);
    }

    /**
     * Clear headers
     *
     * @param string[]|null $filter
     * @return $this
     */
    public function clearHeaders(array $filter = null)
    {
        if($filter !== null){
            $this->headers = $this->map = [];
        } else {
            foreach ($filter as $header){
                $header = strtolower($header);
                if(isset($this->map[$header])){
                    unset($this->headers[$this->map[$header]]);
                    unset($this->map[$header]);
                }
            }
        }

        return $this;
    }

    /**
     * Set a cookie
     *
     * @param string $name
     * @param string $value
     * @param int $ttl
     * @param string[] $options
     * @return $this
     */
    public function setCookie(string $name, string $value, int $ttl = 0, array $options = [])
    {
        $ttl = ($ttl > 0) ? (time() + $ttl) : 0;
        $defaults = ['path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false];
        $this->cookies[] = ['name' => $name, 'value' => $value, 'ttl' => $ttl] + $options + $defaults;
        return $this;
    }

    /**
     * Delete a cookie
     *
     * @param string $name
     * @param array $options
     * @return Response
     */
    public function deleteCookie(string $name, array $options = [])
    {
        return $this->setCookie($name, '', time() - 3600, $options);
    }

    /**
     * Get cookies
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Clear cookies
     *
     * @return $this
     */
    public function clearCookies()
    {
        $this->cookies = array();
        return $this;
    }
}
