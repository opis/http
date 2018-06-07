<?php
/* ===========================================================================
 * Copyright © 2013-2018 The Opis Project
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

use RuntimeException,
    InvalidArgumentException;

class Stream implements IStream
{
    /** @var null|resource */
    protected $resource = null;

    /** @var null|string */
    protected $to_string = null;

    /**
     * @param resource|string $stream
     * @param string $mode
     */
    public function __construct($stream, string $mode = 'rw')
    {
        if (is_string($stream)) {
            $resource = @fopen($stream, $mode);
            if ($resource === false) {
                throw new InvalidArgumentException("Invalid stream {$stream}");
            }
            $stream = $resource;
            unset($resource);
        } elseif (!is_resource($stream)) {
            throw new InvalidArgumentException("Stream must be a resource or a string");
        }

        if (get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException("Resource must be a stream");
        }

        $this->resource = $stream;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($res = $this->detach()) {
            fclose($res);
        }
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        $res = $this->resource;
        $this->resource = null;
        return $res;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        if (!$this->resource) {
            return null;
        }
        return fstat($this->resource)['size'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function tell(): int
    {
        if (!$this->resource) {
            throw new RuntimeException("No resource available");
        }

        $pos = ftell($this->resource);
        if ($pos === false) {
            throw new RuntimeException("Tell operation failed");
        }

        return $pos;
    }

    /**
     * @inheritDoc
     */
    public function eof(): bool
    {
        return !$this->resource || feof($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return $this->resource ? stream_get_meta_data($this->resource)['seekable'] : false;
    }

    /**
     * @inheritDoc
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available');
        }

        if (fseek($this->resource, $offset, $whence) !== 0) {
            throw new RuntimeException('Seek operation failed');
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        $mode = $this->getMetadata('mode');

        if (!$mode) {
            return false;
        }

        $flags = ['w', 'a', 'x', 'c'];
        if (!isset($mode[1])) {
            return in_array($mode, $flags);
        }

        array_unshift($flags, '+');

        foreach ($flags as $f) {
            if (strpos($mode, $f) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): int
    {
        if (!$this->resource) {
            throw new RuntimeException("No resource available");
        }

        $len = fwrite($this->resource, $string);

        if ($len === false) {
            throw new RuntimeException("Write operation failed");
        }

        return $len;
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        $mode = $this->getMetadata('mode');

        if (!$mode) {
            return false;
        }

        if (strpos($mode, 'r') !== false) {
            return true;
        }

        if (strpos($mode, '+') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function read(int $length): string
    {
        if (!$this->resource) {
            throw new RuntimeException("No resource available");
        }

        $result = fread($this->resource, $length);

        if ($result === false) {
            throw new RuntimeException("Read operation failed");
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        $result = stream_get_contents($this->resource);

        if ($result === false) {
            throw new RuntimeException('Unable to read contents');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(string $key = null)
    {
        if (!$this->resource) {
            return null;
        }

        $meta = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        if (!$this->resource) {
            return '';
        }

        if ($this->to_string !== null) {
            return $this->to_string;
        }

        $current = ftell($this->resource);
        $seek = fseek($this->resource, 0) === 0;
        $contents = stream_get_contents($this->resource);
        if ($seek && $current !== false) {
            fseek($this->resource, $current);
        }

        $this->to_string = $contents === false ? '' : $contents;

        return $this->to_string;
    }

    /**
     * @inheritDoc
     */
    public function __destruct()
    {
        $this->close();
    }
}