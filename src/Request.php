<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use Opis\Stream\{IStream, Stream};

class Request extends Message
{
    /** @var string */
    protected $method;

    /** @var string */
    protected $requestTarget;

    /** @var bool */
    protected $secure;

    /** @var Uri */
    protected $uri;

    /** @var array|null */
    protected $cookies;

    /** @var array */
    protected $files;

    /** @var array|null */
    protected $query;

    /** @var array|null */
    protected $formData;

    /** @var ServerVariables */
    protected $serverVars;

    /**
     * Request constructor.
     * @param string $method
     * @param string $requestTarget
     * @param string $protocolVersion
     * @param bool $secure
     * @param array $headers
     * @param array $files
     * @param null|IStream $body
     * @param array|null $cookies
     * @param array|null $query
     * @param array|null $formData
     * @param ServerVariables|null $serverVars
     */
    public function __construct(
        string $method = 'GET',
        string $requestTarget = '/',
        string $protocolVersion = 'HTTP/1.1',
        bool $secure = false,
        array $headers = [],
        array $files = [],
        ?IStream $body = null,
        ?array $cookies = null,
        ?array $query = null,
        ?array $formData = null,
        ServerVariables $serverVars = null
    ) {

        $this->method = strtoupper($method);
        $this->requestTarget = $requestTarget;
        $this->files = $files;
        $this->secure = $secure;

        if (!in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $body = null;
        } else {
            if ($body === null) {
                $body = new Stream('php://input');
            }
        }

        $this->cookies = $cookies;
        $this->query = $query;
        $this->formData = $formData;
        $this->serverVars = $serverVars ?? new ServerVariables();

        parent::__construct($body, $headers, $protocolVersion);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
    {
        if ($this->uri === null) {
            $uri = new Uri($this->requestTarget);

            $components = $uri->getComponents();

            if (!isset($components['host'])) {
                if (isset($this->headers['Host'])) {
                    $port = null;
                    $host = $this->headers['Host'];
                    if (strpos($host, ':') !== false) {
                        list($host, $port) = explode(':', $host);
                        $port = (int) $port;
                        if ($port < 0 || $port > 65535) {
                            $port = null;
                        }
                    }
                    $components['host'] = $host;
                    $components['port'] = $port;
                    $components['authority'] = $port === null ? $host : $host . ':' . $port;
                }
            }

            if (isset($components['host'])) {
                if (!isset($components['scheme'])) {
                    $components['scheme'] = $this->secure ? 'https' : 'http';
                }
                $uri = new Uri($components);
            }

            $this->uri = $uri;
        }

        return $this->uri;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return isset($this->getCookies()[$name]);
    }

    /**
     * @param string $name
     * @param bool $decode
     * @return string|null
     */
    public function getCookie(string $name, bool $decode = true): ?string
    {
        $cookie = $this->getCookies()[$name] ?? null;

        if ($decode && $cookie !== null) {
            return rawurldecode($cookie);
        }

        return $cookie;
    }

    /**
     * @return array
     */
    public function getCookies(): array
    {
        if ($this->cookies === null) {
            $result = [];
            $cookies = explode('; ', $this->headers['Cookie']);
            foreach ($cookies as $cookie) {
                list($name, $value) = explode('=', $cookie, 2);
                $name = trim($name);
                if (empty($name)) {
                    continue;
                }
                $result[$name] = trim($value, '"');
            }
            $this->cookies = $result;
        }

        return $this->cookies;
    }

    /**
     * @return IUploadedFile[]
     */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        if ($this->query === null) {
            $query = $this->getUri()->getQuery();
            if ($query === null) {
                $query = [];
            } else {
                parse_str($query, $query);
            }
            $this->query = $query;
        }

        return $this->query;
    }

    /**
     * @return array
     */
    public function getFormData(): array
    {
        if ($this->formData === null) {
            $data = [];
            if (isset($this->headers['Content-Type']) && 0 === strpos($this->headers['Content-Type'],
                    'application/x-www-form-urlencoded') && $this->body !== null) {
                parse_str((string)$this->body, $data);
            }
            $this->formData = $data;
        }

        return $this->formData;
    }

    /**
     * @return ServerVariables
     */
    public function getServerVariables(): ServerVariables
    {
        return $this->serverVars;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function query(string $name, $default = null)
    {
        return $this->getQuery()[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function formData(string $name, $default = null)
    {
        return $this->getFormData()[$name] ?? $default;
    }

    /**
     * @param string $name
     * @return null|IUploadedFile
     */
    public function file(string $name): ?IUploadedFile
    {
        return $this->getUploadedFiles()[$name] ?? null;
    }

    /**
     * @return Request
     */
    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } elseif (isset($_POST['x_http_method_override'])) {
                $method = strtoupper($_POST['x_http_method_override']);
            }
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            if (strpos($key, 'HTTP_') === 0) {
                $key = substr($key, 5);
            } elseif (!in_array($key, ['CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'])) {
                continue;
            }
            $key = implode('-', explode('_', $key));
            $headers[$key] = $value;
        }

        if (isset($_SERVER['PATH_INFO'])) {
            $requestTarget = $_SERVER['PATH_INFO'];
            if (isset($_SERVER['QUERY_STRING'])) {
                $requestTarget .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestTarget = $_SERVER['REQUEST_URI'] ?? '/';
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $files = UploadedFile::parseFiles($_FILES);
        $serverVariables = new ServerVariables($_SERVER);

        return new self($method, $requestTarget, $protocol, $secure, $headers, $files, null, $_COOKIE, $_GET, $_POST,
            $serverVariables);
    }
}