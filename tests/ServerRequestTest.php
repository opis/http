<?php
/* ============================================================================
 * Copyright © 2013-2018 Opis
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

namespace Opis\Http\Test;

use Opis\Http\ServerRequest;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase
{

    public function testGet()
    {
        $r = ServerRequest::fromGlobals([
            'HTTPS' => 'off',
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test.php?q=s',

            'HTTP_X_CUSTOM' => 'x-value',
            'HTTP_COOKIE' => 'a=abc; b=123'
        ]);

        $this->assertEquals('http://example.com/test.php?q=s', $r->getUri());
        $this->assertEquals(['q' => 's'], $r->getQueryParams());
        $this->assertEquals('GET', $r->getMethod());
        $this->assertTrue($r->hasHeader('x-custom'));
        $this->assertEquals('x-value', $r->getHeaderLine('x-custom'));
        $this->assertEquals(['a' => 'abc', 'b' => '123'], $r->getCookieParams());
    }

    public function testPost()
    {
        $r = ServerRequest::fromGlobals([
            'HTTPS' => 'on',
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/test.php',

            'HTTP_X_CUSTOM' => 'x-value',
            'HTTP_COOKIE' => 'a=abc; b=123'
        ], null, ['post' => 'value']);

        $this->assertEquals('https://example.com/test.php', $r->getUri());
        $this->assertEquals(['post' => 'value'], $r->getParsedBody());
    }
}