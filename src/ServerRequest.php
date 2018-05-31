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
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array  */
    protected $attributes = [];

    /** @var   array */
    protected $serverParams = null;

    /** @var  array */
    protected $cookieParams = null;

    /** @var  array */
    protected $queryParams = null;

    /** @var  array */
    protected $uploadedFiles = null;

    /** @var  mixed */
    protected $parsedBody = false;


    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $query = [],
        $parsedBody = null,
        string $requestTarget = null,
        string $protocolVersion = null
    )
    {
        if ($method === null) {
            $method = isset($serverParams['REQUEST_METHOD']) ? strtoupper($serverParams['REQUEST_METHOD']) : 'GET';
        }
        parent::__construct($uri, $method, $body, $headers, $requestTarget, $protocolVersion);

        $this->serverParams = $serverParams;
        $this->uploadedFiles = $uploadedFiles;

        $this->cookieParams = $cookies;
        $this->queryParams = $query;
        $this->parsedBody = $parsedBody;
    }


    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        if(null === $this->serverParams){
            $this->serverParams = $_SERVER ?? [];
        }
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        if(null === $this->cookieParams){
            $this->cookieParams = $_COOKIE ?? [];
        }
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $request = clone $this;
        $request->cookieParams = $cookies;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        if(null === $this->queryParams){
            $qs = $this->uri->getQuery();
            if ($qs === '') {
                $this->queryParams = [];
            }
            else {
                parse_str($qs, $this->queryParams);
                if (!is_array($this->queryParams)) {
                    $this->queryParams = [];
                }
            }

        }
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $request = clone $this;
        $request->queryParams = $query;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        // TODO: implement
        if(null === $this->uploadedFiles){
        }
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        // TODO: clone
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        // TODO: implement
        if(false === $this->parsedBody){

        }
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        if ($data !== null && is_scalar($data)) {
            throw new InvalidArgumentException("Invalid parsed body");
        }
        $request = clone $this;
        $request->parsedBody = $data;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->attributes[$name] = $value;
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;
        unset($request->attributes[$name]);
        return $request;
    }
}