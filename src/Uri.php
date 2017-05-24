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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{

    /** @var ServerRequestInterface  */
    protected $request;

    /** @var  string */
    protected $scheme;

    /** @var  string */
    protected $authority;

    /**
     * Uri constructor.
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function getScheme()
    {
        // TODO: Implement getScheme() method.
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        // TODO: Implement getAuthority() method.
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        // TODO: Implement getUserInfo() method.
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        // TODO: Implement getHost() method.
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        // TODO: Implement getPort() method.
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        // TODO: Implement getPath() method.
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        // TODO: Implement getQuery() method.
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        // TODO: Implement getFragment() method.
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        // TODO: Implement withScheme() method.
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        // TODO: Implement withUserInfo() method.
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        // TODO: Implement withHost() method.
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        // TODO: Implement withPort() method.
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        // TODO: Implement withPath() method.
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        // TODO: Implement withQuery() method.
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        // TODO: Implement withFragment() method.
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        // TODO: Implement __toString() method.
    }
}