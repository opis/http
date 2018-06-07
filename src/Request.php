<?php
/* ===========================================================================
 * Copyright 2018 The Opis Project
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
    /** @var string */
    protected $method;

    /** @var string */
    protected $requestTarget;

    /** @var string */
    protected $protocolVersion;

    /** @var bool */
    protected $secure;

    /** @var array */
    protected $headers = [];

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

    /** @var null|IStream|Stream */
    protected $body;

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
        ?array $formData = null
    ) {

        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || !is_string($name)) {
                continue;
            }
            $name = $this->formatHeader($name);
            $this->headers[$name] = trim($value);
        }

        $this->method = strtoupper($method);
        $this->requestTarget = $requestTarget;
        $this->protocolVersion = $protocolVersion;
        $this->files = UploadedFile::parseFiles($files);
        $this->secure = $secure;

        if (!in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $body = null;
        } else {
            if ($body === null) {
                $body = new Stream('php://input', 'r');
            }
        }

        $this->body = $body;
        $this->cookies = $cookies;
        $this->query = $query;
        $this->formData = $formData;
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
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
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
                    $host = new Uri($this->headers['Host']);
                    $components['host'] = $host->getHost();
                    $components['port'] = $host->getPort();
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
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$this->formatHeader($name)]);
    }

    /**
     * @param string $name
     * @return null|string
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$this->formatHeader($name)] ?? null;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return null|IStream
     */
    public function getBody(): ?IStream
    {
        return $this->body;
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
            return urldecode($cookie);
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
                parse_str((string) $this->body,$data);
            }
            $this->formData = $data;
        }

        return $this->formData;
    }

    /**
     * @param string $header
     * @return string
     */
    private function formatHeader(string $header): string
    {
        return ucwords(strtolower(trim($header)), '-');
    }
}