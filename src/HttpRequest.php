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


class HttpRequest extends HttpMessage implements IHttpRequest
{
    /** @var IUri */
    protected $uri;

    /** @var string */
    protected $method;

    /** @var string */
    protected $requestTarget;

    /** @var array */
    protected $cookies;

    /** @var array */
    protected $files;

    /** @var array */
    protected $query;

    /** @var array */
    protected $parsedBody;

    public function __construct(
        IUri $uri = null,
        string $requestTarget = '/',
        string $method = 'GET',
        array $cookies = [],
        array $files = [],
        array $query = [],
        array $parsedBody = [],
        ?IStream $body = null,
        array $headers = [],
        string $protocolVersion = 'HTTP/1.1'
    ) {
        $this->uri = $uri; // build it
        $this->requestTarget = $requestTarget;
        $this->method = $method;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->query = $query;
        $this->parsedBody = $parsedBody;
        parent::__construct($body, $headers, $protocolVersion);
    }

    /**
     * @inheritdoc
     */
    public function getUri(): IUri
    {
        return $this->uri;
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
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function hasCookie(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * @inheritDoc
     */
    public function getCookie(string $name)
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withMethod(string $method): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->method = $method;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withRequestTarget(string $uri): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->requestTarget = $uri;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withUri(IUri $uri): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->uri = $uri;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withCookies(array $cookies): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->cookies = $cookies;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withUploadedFiles(array $files): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->files = $files;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withQuery(array $query): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->query = $query;
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function withParsedBody(array $body): IHttpRequest
    {
        if ($this->locked) {
            throw new \RuntimeException("Immutable object");
        }
        $this->parsedBody = $body;
        return $this;
    }
}