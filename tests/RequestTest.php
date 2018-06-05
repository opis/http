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


use Opis\Http\{
    Request, Stream
};
use Psr\Http\Message\{
    StreamInterface, UriInterface
};
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testHeaderAndMethod()
    {
        $r = new Request('/path/to/something/?q=t');

        $this->assertInstanceOf(UriInterface::class, $r->getUri());
        $this->assertEquals('/path/to/something/?q=t', $r->getUri());

        $this->assertFalse($r->hasHeader('x-test'));

        $r = $r->withHeader('x-test', 'x-value');

        $this->assertTrue($r->hasHeader('x-test'));
        $this->assertEquals('x-value', $r->getHeaderLine('X-Test'));

        $this->assertEquals('GET', $r->getMethod());

        $r = $r->withMethod('POST');

        $this->assertEquals('POST', $r->getMethod());

        $r = $r->withoutHeader('X-Test');

        $this->assertFalse($r->hasHeader('x-test'));
    }

    public function testBody()
    {
        $r = new Request("/", 'POST',"data://text/plain,some data");

        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertEquals('some data', $r->getBody());

        $r2 = $r->withBody(new Stream("data://text/plain,other data"));

        $this->assertEquals('some data', $r->getBody());

        $this->assertEquals('other data', $r2->getBody());
    }
}