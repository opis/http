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


class RequestInfo
{
    /** @var array */
    private $server, $query, $post, $cookies, $files;

    public function __construct(array $server = [], array $query = [], array $post = [], array $cookies = [], array $files = [])
    {
        $this->server = $server ?? $_SERVER;
        $this->query = $query ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->cookies = $cookies ?? $_COOKIE;
        $this->files = UploadedFile::parseFiles($files ?? $_FILES);
    }

    public function getServerParams(): array
    {
        return $this->server;
    }

    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function getPostParams(): array
    {
        return $this->post;
    }

    public function getCookieValues(): array
    {
        return $this->cookies;
    }

    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    public function getRequestMethod(): string
    {

    }

    public function getRequestTarget(): string
    {

    }

    public function getRequestHeaders(): array
    {

    }

    public function getProtocolVersion(): string
    {

    }
}