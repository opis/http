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
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    const URI_REGEX = '`^(?:(?P<scheme>[^:/?#]+):)?(?://(?P<authority>[^/?#]*))?(?P<path>[^?#]*)(?:\?(?P<query>[^#]*))?(?:#(?P<fragment>.*))?`';

    const AUTHORITY_REGEX = '`^(?:(?P<userinfo>[^@]*)@)?(?P<host>[^:]*)(?:\:(?P<port>\d*))?$`';

    const STANDARD_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    /** @var  string[]|null */
    protected $authority;

    /** @var string[] */
    protected $components;

    /**
     * Uri constructor.
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        if (!preg_match(self::URI_REGEX, $uri,$m)) {
            throw new InvalidArgumentException("Invalid URI");
        }

        $this->components = [
            'scheme' => $m['scheme'] ?? '',
            'authority' => $m['authority'] ?? '',
            'path' => $m['path'] ?? '',
            'query' => $m['query'] ?? '',
            'fragment' => $m['fragment'] ?? '',
        ];
        unset($m);

        if ($this->components['authority'] === '') {
            $this->authority = [
                'userinfo' => '',
                'host' => '',
                'port' => null,
            ];
        }
        else {
            if (!preg_match(self::AUTHORITY_REGEX, $this->components['authority'],$authority)) {
                throw new InvalidArgumentException("Invalid URI authority");
            }

            $port = null;
            if (isset($authority['port']) && $authority['port'] !== '') {
                $port = (int) $authority['port'];
                if ($port < 0 || $port > 65535) {
                    throw new InvalidArgumentException("Port outside of range 0-65535");
                }
            }

            $this->authority = [
                'userinfo' => $authority['userinfo'] ?? '',
                'host' => $authority['host'] ?? '',
                'port' => $port,
            ];

            unset($authority);

            $this->components['authority'] = $this->buildAuthority(
                $this->authority['host'],
                $this->normalizePort($port, $this->components['scheme']),
                $this->authority['userinfo']
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getScheme()
    {
        return $this->components['scheme'];
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        return $this->components['authority'];
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        return $this->authority['userinfo'];
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->authority['host'];
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        return $this->normalizePort($this->authority['port'], $this->components['scheme']);
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->components['path'];
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->components['query'];
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        return $this->components['fragment'];
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException("Scheme must be a string");
        }

        $uri = clone $this;
        $scheme = strtolower($scheme);
        $uri->components['scheme'] = $scheme;
        $uri->components['authority'] = $this->buildAuthority(
            $uri->authority['host'],
            $uri->normalizePort($uri->authority['port'], $scheme),
            $uri->authority['userinfo']
        );
        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        if (!is_string($user)) {
            throw new InvalidArgumentException("User must be a string");
        }

        $userInfo = '';
        if ($user !== '') {
            $userInfo = $user;
            if ($password !== null && $password !== '') {
                if (!is_string($password)) {
                    throw new InvalidArgumentException("Password must be a string");
                }
                $userInfo .= ':' . $password;
            }
        }

        $uri = clone $this;
        $uri->authority['userinfo'] = $userInfo;

        $uri->components['authority'] = $uri->buildAuthority(
            $uri->authority['host'],
            $uri->normalizePort($uri->authority['port'], $uri->components['scheme']),
            $userInfo
        );

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException("Host must be a string");
        }

        $uri = clone $this;
        $uri->authority['host'] = $host;

        $uri->components['authority'] = $uri->buildAuthority(
            $host,
            $this->normalizePort($uri->authority['port'], $uri->components['scheme']),
            $uri->authority['userinfo']
        );

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        if ($port === null) {
            $uri = clone $this;
            $uri->components['port'] = $port;
            $uri->components['authority'] = $this->buildAuthority(
                $uri->authority['host'],
                $port,
                $uri->authority['userinfo']
            );
            return $uri;
        }

        if (!is_int($port)) {
            throw new InvalidArgumentException("Port must be an integer");
        }

        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException("Port outside of range 0-65535");
        }

        $uri = clone $this;
        $uri->authority['port'] = $port;

        $uri->components['authority'] = $this->buildAuthority(
            $uri->authority['host'],
            $uri->normalizePort($port, $uri->components['scheme']),
            $uri->authority['userinfo']
        );

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException("Path must be a string");
        }

        $uri = clone $this;
        $uri->components['path'] = $path;
        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        if (!is_string($query)) {
            throw new InvalidArgumentException("Query must be a string");
        }

        $uri = clone $this;
        $uri->components['query'] = $query;
        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        if (!is_string($fragment)) {
            throw new InvalidArgumentException("Fragment must be a string");
        }

        $uri = clone $this;
        $uri->components['fragment'] = $fragment;
        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $uri = '';

        if (isset($this->components['scheme']) && $this->components['scheme'] !== '') {
            $uri .= $this->components['scheme'] . ':';
        }

        $has_authority = false;

        if (isset($this->components['authority']) && $this->components['authority'] !== '') {
            $has_authority = true;
            $uri .= '//' . $this->components['authority'];
        }

        if (isset($this->components['path']) && $this->components['path'] !== '') {
            $path = $this->components['path'];

            if ($has_authority) {
                if ($path[0] !== '/') {
                    $path = '/' . $path;
                }
            }
            elseif ($path[0] === '/') {
                $path = '/' . ltrim($path, '/');
            }

            $uri .= $path;
        }

        if (isset($this->components['query']) && $this->components['query'] !== '') {
            $uri .= '?' . $this->components['query'];
        }

        if (isset($this->components['fragment']) && $this->components['fragment'] !== '') {
            $uri .= '#' . $this->components['fragment'];
        }

        return $uri;
    }

    /**
     * @param int|null $port
     * @param string|null $scheme
     * @return int|null
     */
    protected function normalizePort(int $port = null, string $scheme = null)
    {
        if ($port === null || $scheme === null || $scheme === '' || !isset(self::STANDARD_PORTS[$scheme])) {
            return $port;
        }

        return $port == self::STANDARD_PORTS[$scheme] ? null : $port;
    }

    /**
     * @param string|null $host
     * @param int|null $port
     * @param string|null $userInfo
     * @return string
     */
    protected function buildAuthority(string $host = null, int $port = null, string $userInfo = null): string
    {
        if ($host === null || $host === '') {
            return '';
        }

        $authority = '';

        if ($userInfo !== null && $userInfo !== '') {
            $authority .= $userInfo . '@';
        }

        $authority .= $host;

        if ($port !== null) {
            $authority .= ':' . $port;
        }

        return $authority;
    }
}