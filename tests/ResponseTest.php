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

use Opis\Http\Response;
use Opis\Http\Responses\{
    EmptyResponse,
    StringResponse,
    JsonResponse,
    HtmlResponse,
    RedirectResponse
};
use Opis\Http\Stream;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testEmptyDefault()
    {
        $response = new EmptyResponse();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
        $this->assertEmpty($response->getCookies());
        $this->assertEquals(null, $response->getBody());
    }

    public function testString()
    {
        $response = new StringResponse("test", 200, [
            "X-Custom" => "Custom Header",
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("4", $response->getHeader("Content-Length"));
        $this->assertTrue($response->hasHeader("x-custom"));
        $this->assertEquals("test", $response->getBody());
    }

    public function testStream()
    {
        $data = "some text";

        $stream = new Stream("data://text/plain;base64," . base64_encode($data), "r");
        $response = new Response(200, [], $stream);

        $this->assertEquals($data, $response->getBody());
        $this->assertEmpty($response->getHeaders());
    }

    public function testJson()
    {
        $json = [1, 2, "abc", ["x" => 1]];

        $response = new JsonResponse($json);

        $this->assertEquals($json, json_decode($response->getBody(), true));
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertTrue($response->hasHeader("Content-Length"));
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader("Content-Type"));
    }

    public function testHtml()
    {
        $html = "<html><body>hello</body></html>";

        $response = new HtmlResponse($html);

        $this->assertEquals($html, $response->getBody());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertTrue($response->hasHeader("Content-Length"));
        $this->assertEquals('text/html; charset=utf-8', $response->getHeader("Content-Type"));
        $this->assertEquals((string)strlen($html), $response->getHeader("Content-Length"));
    }

    public function testRedirect()
    {
        $response = new RedirectResponse("/new/path");

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertEquals("/new/path", $response->getHeader("Location"));
        $this->assertEquals(null, $response->getBody());
    }
}