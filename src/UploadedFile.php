<?php
/* ============================================================================
 * Copyright © 2013-2018 The Opis project
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

use InvalidArgumentException, RuntimeException;
use Psr\Http\Message\{
    StreamInterface, UploadedFileInterface
};

class UploadedFile implements UploadedFileInterface
{
    /** @var string */
    protected $name;

    /** @var string|null */
    protected $type;

    /** @var int */
    protected $error;

    /** @var int|null */
    protected $size;

    /** @var StreamInterface|null */
    protected $stream = null;

    /** @var null|string */
    protected $file = null;

    /** @var bool */
    protected $moved = false;

    public function __construct($file, string $name = null, int $size = null, string $type = null, int $error = UPLOAD_ERR_OK)
    {
        if (is_string($file)) {
            if (PHP_SAPI === 'cli') {
                if (!is_file($file)) {
                    throw new RuntimeException("File {$file} does not exists");
                }
            }
            else {
                if (!is_uploaded_file($file)) {
                    throw new RuntimeException("File {$file} was not uploaded");
                }
            }
            $this->file = $file;
        }
        elseif (is_resource($file)) {
            $this->stream = new Stream($file, 'r');
        }
        elseif ($file instanceof StreamInterface) {
            $this->stream = $file;
        }
        else {
            throw new InvalidArgumentException("Invalid value for file");
        }

        $this->name = null;
        $this->size = $size;
        $this->type = $type;
        $this->error = $error;

        if ($this->stream) {
            if ($this->size === null) {
                $this->size = $this->stream->getSize();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        if ($this->stream === null) {
            $this->stream = new Stream($this->file, 'r');
        }
        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new RuntimeException("File was already moved");
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("File was not properly uploaded");
        }

        // TODO:

        $this->moved = true;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->type;
    }
}