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

class Uri
{
    const URI_REGEX = '`^(?:(?P<scheme>[^:/?#]+):)?(?://(?P<authority>[^/?#]*))?(?P<path>[^?#]*)(?:\?(?P<query>[^#]*))?(?:#(?P<fragment>.*))?`';

    const AUTHORITY_REGEX = '`^(?:(?P<user_info>[^@]*)@)?(?P<host>[^:]*)(?:\:(?P<port>\d*))?$`';

    const STANDARD_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    /** @var string[] */
    protected $components;

    /**
     * Uri constructor.
     * @param string|array $uri
     */
    public function __construct($uri)
    {
        if (is_array($uri)) {
            $uri += [
                'scheme' => null,
                'authority' => null,
                'user_info' => null,
                'host' => null,
                'port' => null,
                'path' => '',
                'query' => null,
                'fragment' => null
            ];
            $this->components = $uri;
            return;
        }

        if (!preg_match(self::URI_REGEX, $uri,$m)) {
            throw new InvalidArgumentException("Invalid URI");
        }

        $this->components = [
            'scheme' => $m['scheme'] ?: null,
            'authority' => $m['authority'] ?: null,
            'path' => $m['path'] ?? '',
            'query' => $m['query'] ?? null,
            'fragment' => $m['fragment'] ?? null,
        ];
        unset($m);


        if ($this->components['authority'] === null) {
            $this->components += [
                'user_info' => null,
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
                'user_info' => $authority['user_info'] ?? null,
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
        return $this->components['user_info'];
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
    public function __toString()
    {
        $uri = '';

        if (isset($this->components['scheme'])) {
            $uri .= $this->components['scheme'] . ':';
        }

        $has_authority = false;

        if (isset($this->components['authority'])) {
            $has_authority = true;
            $uri .= '//' . $this->components['authority'];
        }

        if (isset($this->components['path']) && $this->components['path'] !== '') {
            $path = $this->components['path'];

            if ($has_authority) {
                if ($path[0] !== '/') {
                    $path = '/' . $path;
                }
            } elseif ($path[0] === '/') {
                $path = '/' . ltrim($path, '/');
            }

            $uri .= $path;
        }

        if (isset($this->components['query'])) {
            $uri .= '?' . $this->components['query'];
        }

        if (isset($this->components['fragment'])) {
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
}