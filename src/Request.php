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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface, ServerRequestInterface
{
    /** @var array  */
    protected $attributes = [];

    /** @var   array */
    protected $serverParams;

    /** @var  array */
    protected $cookieParams;

    /** @var  array */
    protected $queryParams;

    /** @var  array */
    protected $uploadedFiles;

    /** @var  mixed */
    protected $parsedBody = false;

    /** @var  string|null */
    protected $requestTarget;

    /** @var  string */
    protected $method;

    /** @var  UriInterface|null */
    protected $uri;

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        if($this->requestTarget === null){
            if($this->uri === null){
                return '/';
            } else {
                return $this->uri->getPath();
            }
        }
        return $this->requestTarget;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $this->requestTarget = $requestTarget;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        if(null === $this->method){
            $server = $this->getServerParams();
            $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
            if($method === 'POST'){
                if(isset($server['HTTP_X_HTTP_METHOD_OVERRIDE'])){
                    $method = strtoupper($server['HTTP_X_HTTP_METHOD_OVERRIDE']);
                } elseif(null !== $body = $this->getParsedBody()){
                    $method = strtoupper($body[$this->withAttribute('method', 'POST')]);
                }
                if(!in_array($method, ['PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT', 'GET'])){
                    $method = 'POST';
                }
            }
            $this->method = $method;
        }
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        if(null === $this->uri){
            $this->uri = new Uri($this);
        }
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $host = $uri->getHost();

        if($host && (!$preserveHost || $this->getHeaderLine('Host') === '')){
            $this->withHeader('Host', $host);
        }
        $this->uri = $uri;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        if(null === $this->serverParams){
            $this->serverParams = $_SERVER;
        }
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        if(null === $this->cookieParams){
            $this->cookieParams = $_COOKIE;
        }
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $this->cookieParams = $cookies;
        return;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        if(null === $this->queryParams){
            $this->queryParams = $_GET;
        }
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $this->queryParams = $query;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        if(null === $this->uploadedFiles){
        }
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        if(false === $this->parsedBody){

        }
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        $this->parsedBody = $data;
        return $this;
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
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        unset($this->attributes[$name]);
        return $this;
    }
}