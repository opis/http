<?php
/* ===========================================================================
 * Copyright 2013-2018 The Opis Project
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

interface IHttpResponse extends IHttpMessage
{
    /**
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * @return string
     */
    public function getReasonPhrase(): string;

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $http_only
     * @return IHttpResponse
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $http_only = false
    ): self;

    /**
     * @param string $name
     * @return IHttpResponse
     */
    public function removeCookie(string $name): self;

    /**
     * @param string $name
     * @return bool
     */
    public function hasCookie(string $name): bool;

    /**
     * @return IHttpResponse
     */
    public function clearCookies(): self;

    /**
     * @param int $code
     * @return IHttpResponse
     */
    public function withStatusCode(int $code): self;

    /**
     * @param string $phrase
     * @return IHttpResponse
     */
    public function withReasonPhrase(string $phrase): self;
}