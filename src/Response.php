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

class Response extends Message
{
    /** @var array */
    protected $cookies = [];

    /** @var int */
    protected $statusCode;

    /** @var bool */
    private $locked = true;

    /** @var array */
    const HTTP_STATUS = [
        // 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        // 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Response constructor.
     * @param int $statusCode
     * @param array $headers
     * @param null|IStream $body
     * @param string $protocolVersion
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        IStream $body = null,
        string $protocolVersion = 'HTTP/1.1'
    ) {
        $this->statusCode = $statusCode;
        parent::__construct($body, $headers, $protocolVersion);
    }

    /**
     * @param callable $callback
     * @return Response
     */
    public function modify(callable $callback): self
    {
        $response = clone $this;
        $response->locked = false;
        $callback($response);
        $response->locked = true;
        return $response;
    }

    /**
     * @param string $version
     * @return Response
     */
    public function setProtocolVersion(string $version): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * @param int $code
     * @return Response
     */
    public function setStatusCode(int $code): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->statusCode = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return self::HTTP_STATUS[$this->statusCode] ?? '';
    }

    /**
     * @param string $name
     * @param string $value
     * @return Response
     */
    public function setHeader(string $name, string $value): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->headers[$this->formatHeader($name)] = trim($value);

        return $this;
    }

    /**
     * @param array $headers
     * @return Response
     */
    public function addHeaders(array $headers): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        foreach ($this->filterHeaders($headers) as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @param null|IStream $body
     * @return Response
     */
    public function setBody(?IStream $body): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->body = $body;
        return $this;
    }

    /**
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $http_only
     * @return Response
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $http_only = false
    ): self {

        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $id = md5(serialize([$name, $path, $domain]));
        $this->cookies[$id] = [
            'name' => $name,
            'value' => rawurlencode($value),
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'http_only' => $http_only,
        ];
        return $this;
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return null|string
     */
    public function getCookie(string $name, string $path = '', string $domain = ''): ?string
    {
        $id = md5(serialize([$name, $path, $domain]));
        return isset($this->cookies[$id]) ? rawurldecode($this->cookies[$id]['value']) : null;
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return bool
     */
    public function hasCookie(string $name, string $path = '', string $domain = ''): bool
    {
        return isset($this->cookies[md5(serialize([$name, $path, $domain]))]);
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return Response
     */
    public function clearCookie(string $name, string $path = '', string $domain = ''): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $id = md5(serialize([$name, $path, $domain]));
        unset($this->cookies[$id]);
        return $this;
    }

    /**
     * @return Response
     */
    public function clearCookies(): self
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->cookies = [];
        return $this;
    }
}