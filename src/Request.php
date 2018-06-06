<?php
/* ============================================================================
 * Copyright © 2013-2018 The Opis project
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
    RequestInterface, StreamInterface, UriInterface
};

class Request extends Message
{
    /** @var null|string */
    protected $requestTarget = null;

    /** @var UriInterface */
    protected $uri = null;

    /** @var string */
    protected $method = null;

    /**
     * @param string|UriInterface|null $uri
     * @param string $method
     * @param null|string|resource|StreamInterface $body
     * @param string[]|string[][]|null $headers
     * @param string|null $requestTarget
     * @param string|null $protocolVersion
     */
    public function __construct(
        $uri = null,
        string $method = 'GET',
        $body = null,
        array $headers = null,
        string $requestTarget = null,
        string $protocolVersion = null
    ) {
        parent::__construct($body, $headers, $protocolVersion);
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri ?? '');
        }
        $this->uri = $uri;
        $this->method = $method;
        $this->requestTarget = $requestTarget;

        // Set host from URI if not in headers
        if (!isset($headers['host'])) {
            $host = $this->uri->getHost();
            if ($host !== '') {
                $port = $this->uri->getPort();
                if ($port !== null) {
                    $host .= ':' . $port;
                }
                $this->headers[] = [
                    'name' => 'Host',
                    'value' => [$host],
                ];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException("Method must be a string");
        }
        $request = clone $this;
        $request->method = $method;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $request = clone $this;

        $request->uri = $uri;
        $host = $uri->getHost();

        if ($preserveHost) {
            if ($host !== '') {
                $host_header = null;
                if (isset($request->headers['host'])) {
                    $host_header = $request->getHeaderLine('host');
                    if ($host_header === '') {
                        $host_header = null;
                    }
                }
                if ($host_header === null) {
                    $this->headers['host'] = [
                        'name' => 'Host',
                        'value' => [$host],
                    ];
                }
            }
        } elseif ($host !== '') {
            $this->headers['host'] = [
                'name' => 'Host',
                'value' => [$host],
            ];
        }

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget === null) {
            $this->requestTarget = $this->uri->getPath();
            $qs = $this->uri->getQuery();
            if ($qs !== '') {
                $this->requestTarget .= '?' . $qs;
            }
            if ($this->requestTarget === '') {
                $this->requestTarget = '/';
            }
        }
        return $this->requestTarget;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $request = clone $this;
        $request->requestTarget = $requestTarget;
        return $request;
    }

}