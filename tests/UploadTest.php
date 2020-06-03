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

use RuntimeException;
use Opis\Stream\{Stream, ResourceStream};
use Opis\Http\UploadedFileHandler;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    public function testOne()
    {
        $size = filesize(__FILE__);
        $f = new UploadedFileHandler(__FILE__, 'Name', $size, 'text/plain');

        $this->assertEquals($size, $f->getSize());
        $this->assertEquals('Name', $f->getClientFilename());
        $this->assertEquals('text/plain', $f->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_OK, $f->getError());
        $this->assertInstanceOf(Stream::class, $f->getStream());

        $target = new ResourceStream('php://memory', 'wb+');
        $f->moveToStream($target);

        $this->assertTrue($f->wasMoved());

        $this->assertEquals(file_get_contents(__FILE__), $target);


        // Cannot be moved twice
        $this->expectException(RuntimeException::class);
        $f->moveToStream($target);
    }

    public function testArray()
    {
        $f = UploadedFileHandler::factory([
            'tmp_name' => 'some-file',
            'name' => 'Name',
            'error' => UPLOAD_ERR_CANT_WRITE
        ]);

        $this->assertEquals('Name', $f->getClientFilename());
        $this->assertNull($f->getSize());
        $this->assertNull($f->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_CANT_WRITE, $f->getError());

        // Cannot be moved because contains error
        $this->expectException(RuntimeException::class);
        $f->moveToFile('somewhere');
    }
}