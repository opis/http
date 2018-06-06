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

interface IHttpRequest extends IHttpMessage
{
    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return string
     */
    public function getRequestTarget(): string;

    /**
     * @return IUri
     */
    public function getUri(): IUri;

    /**
     * @param string $name
     * @return bool
     */
    public function hasCookie(string $name): bool;

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getCookie(string $name);

    /**
     * @return array
     */
    public function getCookies(): array;

    /**
     * @return IUploadedFile[]
     */
    public function getUploadedFiles(): array;

    /**
     * @return array
     */
    public function getQuery(): array;

    /**
     * @return array
     */
    public function getParsedBody(): array;

    /**
     * @param string $method
     * @return IHttpRequest
     */
    public function withMethod(string $method): self;

    /**
     * @param string $uri
     * @return IHttpRequest
     */
    public function withRequestTarget(string $uri): self;

    /**
     * @param IUri $uri
     * @return IHttpRequest
     */
    public function withUri(IUri $uri): self;

    /**
     * @param array $cookies
     * @return IHttpRequest
     */
    public function withCookies(array $cookies): self;

    /**
     * @param array $files
     * @return IHttpRequest
     */
    public function withUploadedFiles(array $files): self;

    /**
     * @param array $query
     * @return IHttpRequest
     */
    public function withQuery(array $query): self;

    /**
     * @param array $body
     * @return IHttpRequest
     */
    public function withParsedBody(array $body): self;
}