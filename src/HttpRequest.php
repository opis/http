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


class HttpRequest extends HttpMessage
{
    /** @var Uri */
    protected $uri;

    /** @var string */
    protected $method;

    /** @var string */
    protected $requestTarget;

    /**
     * HttpRequest constructor.
     * @param RequestInfo|null $requestInfo
     * @param string|null $requestTarget
     * @param string|null $method
     * @param Stream|null $body
     * @param array|null $headers
     * @param string|null $protocolVersion
     */
    public function __construct(
        RequestInfo $requestInfo = null,
        string $requestTarget = null,
        string $method = null,
        Stream $body = null,
        array $headers = null,
        string $protocolVersion = null
    ) {

        $this->method = $method;
        $this->requestTarget = $requestTarget;

        parent::__construct($body, $headers, $protocolVersion);
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
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
}