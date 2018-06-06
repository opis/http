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

    public function __construct(string $requestTarget, string $method = 'GET', array $server = [], array $query = [], array $post = [], array $cookies = [], array $files = [])
    {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Opis Http 3.x',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ], $server);

        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);

        $uri = new Uri($requestTarget);

        if (!empty($uri_host)) {
            $server['SERVER_NAME'] = $server['HOST'] = $uri_host;
        }
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