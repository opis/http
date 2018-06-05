<?php
/* ===========================================================================
 * Copyright © 2013-2018 The Opis Project
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

use InvalidArgumentException;
use Psr\Http\Message\{
    ServerRequestInterface,
    StreamInterface,
    UploadedFileInterface,
    UriInterface
};

class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array */
    protected $attributes = [];

    /** @var   array */
    protected $serverParams;

    /** @var  array */
    protected $cookieParams;

    /** @var  array */
    protected $queryParams;

    /** @var  array */
    protected $uploadedFiles;

    /** @var  array|object|null */
    protected $parsedBody;

    /**
     * @param array $serverParams
     * @param UploadedFileInterface[]|UploadedFileInterface[][] $uploadedFiles
     * @param null|string|UriInterface $uri
     * @param null|string $method
     * @param string|resource|StreamInterface $body
     * @param string[]|string[][] $headers
     * @param array $cookies
     * @param array $query
     * @param mixed $parsedBody
     * @param string|null $requestTarget
     * @param string|null $protocolVersion
     */
    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        string $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $query = null,
        $parsedBody = null,
        string $requestTarget = null,
        string $protocolVersion = null
    ) {
        if ($method === null) {
            $method = isset($serverParams['REQUEST_METHOD']) ? strtoupper($serverParams['REQUEST_METHOD']) : 'GET';
        }
        parent::__construct($uri, $method, $body, $headers, $requestTarget, $protocolVersion);

        $this->serverParams = $serverParams;
        $this->uploadedFiles = $uploadedFiles;

        $this->cookieParams = $cookies;
        $this->queryParams = $query;
        $this->parsedBody = $parsedBody;
    }


    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $request = clone $this;
        $request->cookieParams = $cookies;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        if (null === $this->queryParams) {
            $qs = $this->uri->getQuery();
            if ($qs === '') {
                $this->queryParams = [];
            } else {
                parse_str($qs, $this->queryParams);
                if (!is_array($this->queryParams)) {
                    $this->queryParams = [];
                }
            }

        }
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $request = clone $this;
        $request->queryParams = $query;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $file) {
            if (!($file instanceof UploadedFileInterface)) {
                throw new InvalidArgumentException("Invalid uploaded file");
            }
        }
        $request = clone $this;
        $request->uploadedFiles = $uploadedFiles;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        if ($data !== null && is_scalar($data)) {
            throw new InvalidArgumentException("Invalid parsed body");
        }
        $request = clone $this;
        $request->parsedBody = $data;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->attributes[$name] = $value;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;
        unset($request->attributes[$name]);
        return $request;
    }

    /**
     * @param array|null $server
     * @param array|null $query
     * @param array|null $body
     * @param array|null $cookies
     * @param array|null $files
     * @return self
     */
    public static function factory(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ): self {

        $server = $server ?: $_SERVER;

        if (is_callable('\getallheaders')) {
            $headers = getallheaders();
        }
        else {
            $headers = [];
            foreach ($server as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $key = ucwords(str_replace('_', '-', strtolower(substr($key, 5))), '-');
                    $headers[$key] = $value;
                }
            }
        }

        if ($cookies === null) {
            $cookies = $_COOKIE ?? [];
            if (!$cookies && isset($headers['Cookie'])) {
                foreach (explode(';', $headers['Cookie']) as $cookie) {
                    if (strpos($cookie, '=') === false) {
                        continue;
                    }
                    $cookie = explode('=', $cookie, 2);
                    $cookies[ltrim($cookie[0])] = $cookie[1];
                }
            }
        }

        if (isset($server['HTTPS']) && $server['HTTPS'] && $server['HTTPS'] !== 'off') {
            $uri = 'https:';
        }
        else {
            if (isset($headers['X-Forwarded-Proto']) && $headers['X-Forwarded-Proto'] === 'https') {
                $uri = 'https:';
            }
            else {
                $uri = 'http:';
            }
        }

        if (isset($server['SERVER_NAME'])) {
            $uri .= '//' . $server['SERVER_NAME'];
            if (isset($server['SERVER_PORT'])) {
                $uri .= ':' . $server['SERVER_PORT'];
            }
        }

        if (isset($server['REQUEST_URI'])) {
            $uri .= $server['REQUEST_URI'];
        }

        $version = null;
        if (isset($server['SERVER_PROTOCOL'])) {
            if (preg_match('~^HTTP/(?<version>\d(?:\.\d)?)$~i', $server['SERVER_PROTOCOL'], $m)) {
                $version = $m['version'];
            }
        }

        $method = $server['REQUEST_METHOD'] ?? 'GET';
        if ($body === null && $method === 'POST' && isset($_POST)) {
            $body = $_POST;
        }
        if ($query === null && isset($_GET)) {
            $query = $_GET;
        }

        return new self(
            $server,
            UploadedFile::parseFiles($files ?: $_FILES),
            $uri,
            $method,
            "php://input",
            $headers,
            $cookies,
            $query,
            $body,
            null,
            $version
        );
    }
}