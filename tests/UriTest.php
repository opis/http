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

use Opis\Http\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    public function testFull()
    {
        $uri = new Uri('http://user:pass@example.com:555/path/to/file.ext?q=1&t=2#anchor');

        $this->assertEquals('http', $uri->getScheme(), 'scheme');
        $this->assertEquals('user:pass', $uri->getUserInfo(), 'userinfo');
        $this->assertEquals('example.com', $uri->getHost(), 'host');
        $this->assertEquals(555, $uri->getPort(), 'port');
        $this->assertEquals('/path/to/file.ext', $uri->getPath(), 'path');
        $this->assertEquals('q=1&t=2', $uri->getQuery(), 'query');
        $this->assertEquals('anchor', $uri->getFragment(), 'fragment');

        $this->assertEquals('http://user:pass@example.com:555/path/to/file.ext?q=1&t=2#anchor', $uri);
    }

    public function testWithPath()
    {
        $uri = new Uri("http://example.com/path1?qs");
        $uri = $uri->withPath('/some/path');

        $this->assertEquals('/some/path', $uri->getPath());
        $this->assertEquals('http://example.com/some/path?qs', $uri);
    }

    public function testWithHost()
    {
        $uri = new Uri("http://example.com:555/test.html");
        $uri = $uri->withHost('example.test');

        $this->assertEquals('example.test', $uri->getHost());
        $this->assertEquals('http://example.test:555/test.html', $uri);
    }

    public function testPort()
    {
        $uri = new Uri("http://example.com:80/test.html");

        $this->assertEquals(null, $uri->getPort());

        $this->assertEquals('example.com', $uri->getAuthority());

        $uri = $uri->withScheme('https');

        $this->assertEquals(80, $uri->getPort());

        $this->assertEquals('example.com:80', $uri->getAuthority());

        $uri = $uri->withPort(22);

        $this->assertEquals(22, $uri->getPort());

        $this->assertEquals('example.com:22', $uri->getAuthority());

        $uri = $uri->withPort(443);

        $this->assertEquals(null, $uri->getPort());

        $this->assertEquals('example.com', $uri->getAuthority());
    }

    public function testRelative()
    {
        $uri = new Uri('path/to/something?qs=t');

        $this->assertEquals('', $uri->getScheme());
        $this->assertEquals('', $uri->getAuthority());
        $this->assertEquals('', $uri->getUserInfo());
        $this->assertEquals('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertEquals('path/to/something', $uri->getPath());
        $this->assertEquals('qs=t', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());

        $this->assertEquals('path/to/something?qs=t', $uri);

        $uri = $uri->withScheme('custom');

        $this->assertEquals('custom:path/to/something?qs=t', $uri);
    }

}