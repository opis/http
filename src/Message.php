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
    MessageInterface, StreamInterface
};

class Message implements MessageInterface
{
    /** @var  string */
    protected $protocolVersion = '1.1';

    /** @var array */
    protected $headers = [];

    /** @var  StreamInterface|null */
    protected $body = null;

    /** @var string|resource|null */
    protected $stream = 'php://temp';

    /**
     * @param null|string|resource|StreamInterface $body
     * @param string[]|string[][]|null $headers
     * @param string|null $protocolVersion
     */
    public function __construct($body = null, array $headers = null, string $protocolVersion = null)
    {
        if ($headers) {
            $this->headers = $this->parseHeaders($headers);
        }
        if ($protocolVersion) {
            $this->protocolVersion = $protocolVersion;
        }

        if ($body === null) {
            return;
        }

        if ($body instanceof StreamInterface) {
            $this->body = $body;
            $this->stream = null;
            return;
        }

        if (is_resource($body)) {
            $this->body = new Stream($body, 'wb+');
            $this->stream = null;
            return;
        }

        if (is_string($body)) {
            $this->stream = $body;
        }
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        if (!is_string($version)) {
            throw new InvalidArgumentException("Protocol version must be a string");
        }
        $obj = clone $this;
        $obj->protocolVersion = $version;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        $list = [];
        foreach ($this->headers as $header) {
            $list[$header['name']] = $header['value'];
        }
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        if (!is_string($name)) {
            return false;
        }
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        if (!is_string($name)) {
            return [];
        }
        return $this->headers[strtolower($name)]['value'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $this->validateHeader($name, $value);

        $obj = clone $this;

        $obj->headers[strtolower($name)] = [
            'name' => $name,
            'value' => (array) $value,
        ];

        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $this->validateHeader($name, $value);

        $obj = clone $this;

        $key = strtolower($name);

        if (isset($obj->headers[$key])) {
            if (is_string($value)) {
                $obj->headers[$key]['value'][] = $value;
            }
            else {
                $obj->headers[$key]['value'] = array_merge($obj->headers[$key]['value'], $value);
            }
        }
        else {
            $obj->headers[$key] = [
                'name' => $name,
                'value' => (array) $value,
            ];
        }

        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $obj = clone $this;
        unset($obj->headers[strtolower($name)]);
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        if ($this->body === null) {
            $this->body = new Stream($this->stream, 'wb+');
            $this->stream = null;
        }
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $obj = clone $this;
        $obj->body = $body;
        return $obj;
    }

    /**
     * @param $name
     * @param $value
     */
    protected function validateHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("Header name must be a string");
        }

        if (!is_array($value)) {
            if (!is_string($value)) {
                throw new InvalidArgumentException("Header value must be a string or an array of strings");
            }
        }
        else {
            foreach ($value as $v) {
                if (!is_string($v)) {
                    throw new InvalidArgumentException("Header value must be a string or an array of strings");
                }
            }
        }
    }

    /**
     * @param array $headers
     * @return array
     */
    protected function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $name => $value) {
            $key = strtolower($name);
            if (is_scalar($value)) {
                $parsed[$key] = [
                    'name' => $name,
                    'value' => [(string)$value]
                ];
                continue;
            }

            if (is_array($value)) {
                $list = [];
                foreach ($value as $v) {
                    if (is_string($v)) {
                        $list[] = $v;
                    }
                }
                if ($list) {
                    $parsed[$key] = [
                        'name' => $name,
                        'value' => $list,
                    ];
                }
                unset($list);
                continue;
            }
        }

        return $parsed;
    }
}