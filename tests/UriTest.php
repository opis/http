<?php
/* ============================================================================
 * Copyright 2018 Zindex Software
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
        $this->assertEquals('user:pass', $uri->getUserInfo(), 'user_info');
        $this->assertEquals('example.com', $uri->getHost(), 'host');
        $this->assertEquals(555, $uri->getPort(), 'port');
        $this->assertEquals('/path/to/file.ext', $uri->getPath(), 'path');
        $this->assertEquals('q=1&t=2', $uri->getQuery(), 'query');
        $this->assertEquals('anchor', $uri->getFragment(), 'fragment');

        $this->assertEquals('http://user:pass@example.com:555/path/to/file.ext?q=1&t=2#anchor', $uri);
    }

    public function testAsterisk()
    {
        $uri = new Uri('*');

        $this->assertEquals('*', $uri->getPath());
        $this->assertEquals('*', (string) $uri);
    }

    public function testFragment()
    {
        $uri = new Uri("/a#foo");
        $this->assertEquals('foo', $uri->getFragment());

        $uri = new Uri("/a#");
        $this->assertEquals("", $uri->getFragment());

        $uri = new Uri("/a");
        $this->assertNull($uri->getFragment());
    }

    public function testQuery()
    {
        $uri = new Uri("/a?foo=bar");
        $this->assertEquals('foo=bar', $uri->getQuery());

        $uri = new Uri("/a?foo");
        $this->assertEquals('foo', $uri->getQuery());

        $uri = new Uri("?foo");
        $this->assertEquals('foo', $uri->getQuery());

        $uri = new Uri("?foo#bar");
        $this->assertEquals('foo', $uri->getQuery());


        $uri = new Uri("/a?");
        $this->assertEquals("", $uri->getQuery());

        $uri = new Uri("?");
        $this->assertEquals("", $uri->getQuery());


        $uri = new Uri("?#");
        $this->assertEquals("", $uri->getQuery());

        $uri = new Uri("/a");
        $this->assertNull($uri->getQuery());

        $uri = new Uri("");
        $this->assertNull($uri->getQuery());
    }

    public function testRelative()
    {
        $uri = new Uri('path/to/something?qs=t');

        $this->assertNull($uri->getScheme(), 'scheme');
        $this->assertNull($uri->getAuthority(), 'authority');
        $this->assertNull($uri->getUserInfo());
        $this->assertNull($uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertNull($uri->getFragment());
        $this->assertEquals('path/to/something', $uri->getPath());
        $this->assertEquals('qs=t', $uri->getQuery());

        $this->assertEquals('path/to/something?qs=t', $uri);
    }

}