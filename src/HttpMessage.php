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

abstract class HttpMessage implements IHttpMessage
{
    /** @var IStream|null */
    protected $body;

    /** @var string */
    protected $protocolVersion;

    /** @var array */
    protected $header_map;

    /** @var array */
    protected $headers;

    /** @var bool */
    protected $locked = true;

    /**
     * @param null|IStream $body
     * @param array $headers
     * @param string $protocolVersion
     */
    public function __construct(?IStream $body, array $headers, string $protocolVersion)
    {
        $formatted_headers = [];
        $map = [];
        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || !is_string($name)) {
                continue;
            }
            $formatted_headers[$name] = (string) $value;
            $map[strtolower($name)] = $name;
        }
        $this->body = $body;
        $this->headers = $formatted_headers;
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
     * @inheritdoc
     */
    public function getBody(): ?IStream
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->header_map[strtolower($name)]);
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $name): ?string
    {
        $name = $this->header_map[strtolower($name)] ?? false;
        return $name === false ? null : $this->headers[$name];
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function modify(callable $callback): IHttpMessage
    {
        $new = clone $this;
        $new->locked = false;
        $callback($new);
        $new->locked = true;
        return $new;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withProtocolVersion(string $version): IHttpMessage
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withHeader(string $name, string $value): IHttpMessage
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->headers[$name] = $value;
        $this->header_map[strtolower($name)] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withHeaders(array $headers): IHttpMessage
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $formatted_list = [];
        $map = [];
        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || !is_string($name)) {
                continue;
            }
            $formatted_list[$name] = (string) $value;
            $map[strtolower($name)] = $name;
        }
        $this->headers = $formatted_list;
        $this->header_map = $map;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withoutHeader(string $name): IHttpMessage
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        unset($this->headers[$name], $this->header_map[strtolower($name)]);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withoutHeaders(string ...$names): IHttpMessage
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        foreach ($names as $name) {
            unset($this->headers[$name], $this->header_map[strtolower($name)]);
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withBody(?IStream $body): IHttpMessage
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->body = $body;
        return $this;
    }
}