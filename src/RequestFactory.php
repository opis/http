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


class RequestFactory
{
    /**
     * Constructor
     */
    private function __construct()
    {
        // Nothig here
    }

    /**
     * @return Request
     */
    public function fromGlobals(): Request
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } elseif (isset($_POST['x_http_method_override'])) {
                $method = strtoupper($_POST['x_http_method_override']);
            }
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            if (strpos($key, 'HTTP_') === 0) {
                $key = substr($key, 5);
            } elseif (!in_array($key, ['CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'])) {
                continue;
            }
            $key = implode('-', explode('_', $key));
            $headers[$key] = $value;
        }

        if (isset($_SERVER['PATH_INFO'])) {
            $requestTarget = $_SERVER['PATH_INFO'];
            if (isset($_SERVER['QUERY_STRING'])) {
                $requestTarget .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestTarget = $_SERVER['REQUEST_URI'] ?? '/';
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        return new Request($method, $requestTarget, $protocol, $secure, $headers, $_FILES, null, $_COOKIE, $_GET, $_POST);
    }

    /**
     * @return RequestFactory
     */
    public static function instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }
}