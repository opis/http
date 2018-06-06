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

class Uri implements IUri
{
    const URI_REGEX = '`^(?:(?P<scheme>[^:/?#]+):)?(?://(?P<authority>[^/?#]*))?(?P<path>[^?#]*)(?:\?(?P<query>[^#]*))?(?:#(?P<fragment>.*))?`';

    const AUTHORITY_REGEX = '`^(?:(?P<userinfo>[^@]*)@)?(?P<host>[^:]*)(?:\:(?P<port>\d*))?$`';

    const STANDARD_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    /** @var string[] */
    protected $components;

    /** @var bool */
    protected $locked = true;

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
            'scheme' => $m['scheme'] ?? null,
            'authority' => $m['authority'] ?? null,
            'path' => $m['path'] ?? '',
            'query' => $m['query'] ?? null,
            'fragment' => $m['fragment'] ?? null,
        ];
        unset($m);

        if ($this->components['authority'] === null) {
            $this->components += [
                'userinfo' => null,
                'host' => null,
                'port' => null,
            ];
        } else {
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

            $this->components += [
                'userinfo' => $authority['userinfo'] ?? null,
                'host' => $authority['host'] ?? null,
                'port' => $port,
            ];

            unset($authority);
        }
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): ?string
    {
        return $this->components['scheme'];
    }

    /**
     * @inheritDoc
     */
    public function getAuthority(): ?string
    {
        return $this->components['authority'];
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo(): ?string
    {
        return $this->components['userinfo'];
    }

    /**
     * @inheritDoc
     */
    public function getHost(): ?string
    {
        return $this->components['host'];
    }

    /**
     * @inheritDoc
     */
    public function getPort(): ?int
    {
        return $this->normalizePort($this->components['port'], $this->components['scheme']);
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->components['path'];
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): ?string
    {
        return $this->components['query'];
    }

    /**
     * @inheritDoc
     */
    public function getFragment(): ?string
    {
        return $this->components['fragment'];
    }

    /**
     * @inheritDoc
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @inheritDoc
     */
    public function modify(callable $callback): IUri
    {
        $new = clone $this;
        $new->locked = false;
        $callback($new);
        $new->locked = true;
        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withScheme(string $scheme): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $scheme = strtolower($scheme);
        $this->components['scheme'] = $scheme;
        $this->components['authority'] = $this->buildAuthority(
            $this->components['host'],
            $this->normalizePort($this->components['port'], $scheme),
            $this->components['userinfo']
        );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo(string $user, string $password = null): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $userInfo = '';
        if ($user !== '') {
            $userInfo = $user;
            if ($password !== null && $password !== '') {
                $userInfo .= ':' . $password;
            }
        }

        $this->components['userinfo'] = $userInfo;

        $this->components['authority'] = $this->buildAuthority(
            $this->components['host'],
            $this->normalizePort($this->components['port'], $this->components['scheme']),
            $userInfo
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withHost(string $host): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        if (!is_string($host)) {
            throw new InvalidArgumentException("Host must be a string");
        }

        $uri = clone $this;
        $uri->components['host'] = $host;

        $uri->components['authority'] = $uri->buildAuthority(
            $host,
            $this->normalizePort($uri->components['port'], $uri->components['scheme']),
            $uri->components['userinfo']
        );

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withPort(?int $port): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        if ($port === null) {
            $this->components['port'] = $port;
            $this->components['authority'] = $this->buildAuthority(
                $this->components['host'],
                $port,
                $this->components['userinfo']
            );
            return $this;
        }

        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException("Port outside of range 0-65535");
        }

        $this->components['port'] = $port;

        $this->components['authority'] = $this->buildAuthority(
            $this->components['host'],
            $this->normalizePort($port, $this->components['scheme']),
            $this->components['userinfo']
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withPath(string $path): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->components['path'] = $path;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withQuery(string $query): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->components['query'] = $query;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withFragment(string $fragment): IUri
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }

        $this->components['fragment'] = $fragment;
        return $this;
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