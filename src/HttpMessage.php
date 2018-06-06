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

abstract class HttpMessage
{
    /** @var Stream|null */
    protected $body;

    /** @var string */
    protected $protocolVersion;

    /** @var array */
    protected $header_map;

    /** @var array */
    protected $headers;

    /**
     * @param Stream|null $body
     * @param array $headers
     * @param string $protocolVersion
     */
    public function __construct(Stream $body = null, array $headers = [], string $protocolVersion = 'HTTP/1.1')
    {
        $raw_headers = [];
        $map = [];

        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || !is_string($name)) {
                continue;
            }
            $raw_headers[$name] = (string) $value;
            $map[strtolower($name)] = $name;
        }

        $this->body = $body;
        $this->headers = $raw_headers;
        $this->header_map = $map;
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @return null|Stream
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->header_map[strtolower($name)]);
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name)
    {
        $name = $this->header_map[strtolower($name)] ?? false;
        return $name === false ? null : $this->headers[$name];
    }
}