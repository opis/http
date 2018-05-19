<?php
/* ===========================================================================
 * Copyright 2013-2017 The Opis Project
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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    /** @var  string|null */
    protected $protocolVersion;

    /** @var array  */
    protected $headers = [];

    /** @var  array|null */
    protected $cacheHeaders;

    /** @var  StreamInterface */
    protected $body;

    public function __construct(string $protocol = '1.1', $headers = [], StreamInterface $body = null)
    {
        $this->protocolVersion = $protocol;
        $this->headers = $headers;
        $this->body = $body;
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
        $obj = clone $this;
        $obj->protocolVersion = $version;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        if($this->cacheHeaders === null){
            foreach ($this->headers as $header){
                $this->cacheHeaders[$header['name']] = $header['value'];
            }
        }
        return $this->cacheHeaders;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
       return $this->headers[strtolower($name)]['value'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $obj = clone $this;

        if(!is_array($value)){
            $value = (array) $value;
        }

        $obj->headers[strtolower($name)] = [
            'name' => $name,
            'value' => $value,
        ];
        $obj->cacheHeaders = null;

        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $obj = clone $this;

        if(!is_array($value)){
            $value = (array) $value;
        }

        $key = strtolower($name);

        if (isset($this->headers[$key])) {
            $header = $this->headers[$key];
        } else {
            $header = [
                'name' => $name,
                'value' => []
            ];
        }

        $header['value'] = array_merge($header['value'], $value);
        $obj->headers[$key] = $header;
        $obj->cacheHeaders = null;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $obj = clone $this;
        unset($obj->headers[strtolower($name)]);
        $obj->cacheHeaders = null;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
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
}