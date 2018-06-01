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

    /**
     * @param string|resource|StreamInterface $file
     * @param string|null $name
     * @param int|null $size
     * @param string|null $type
     * @param int $error
     */
    public function __construct(
        $file,
        string $name = null,
        int $size = null,
        string $type = null,
        int $error = UPLOAD_ERR_OK
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->error = $error;
        if ($error !== UPLOAD_ERR_OK) {
            return;
        }
        $this->size = $size;

        if (is_string($file)) {
            if (substr(PHP_SAPI, 0, 3) === 'cli') {
                if (!is_file($file)) {
                    throw new RuntimeException("File {$file} does not exists");
                }
            } else {
                if (!is_uploaded_file($file)) {
                    throw new RuntimeException("File {$file} was not uploaded");
                }
            }
            $this->file = $file;
        } elseif (is_resource($file)) {
            $this->stream = new Stream($file, 'r');
        } elseif ($file instanceof StreamInterface) {
            $this->stream = $file;
        } else {
            throw new InvalidArgumentException("Invalid value for file");
        }
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        if ($this->moved || $this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Stream is not available");
        }
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

        $targetDir = null;
        if ($targetPath instanceof StreamInterface) {
            if (!$targetPath->isWritable()) {
                throw new InvalidArgumentException("Stream is not writable");
            }
        } elseif (is_string($targetPath) && $targetPath !== '') {
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                throw new RuntimeException("Directory {$targetDir} does not exists");
            }
            if (!is_writable($targetDir)) {
                throw new RuntimeException("Directory {$targetDir} is not writable");
            }
        } else {
            throw new InvalidArgumentException("Target must be a stream or non empty string");
        }

        $ok = true;
        if ($this->file !== null) {
            if ($targetDir !== null) {
                if (substr(PHP_SAPI, 0, 3) === 'cli') {
                    $ok = rename($this->file, $targetPath);
                } else {
                    $ok = is_uploaded_file($this->file) && move_uploaded_file($this->file, $targetPath);
                }
            } else {
                $ok = $this->copyContents(new Stream(realpath($this->file)), $targetPath);
            }
        } elseif ($this->stream !== null) {
            if ($targetDir !== null) {
                $ok = $this->copyContents($this->stream, new Stream(realpath($targetPath), 'wb'));
            } else {
                $ok = $this->copyContents($this->stream, $targetPath);
            }
        }

        $this->moved = true;

        if (!$ok) {
            throw new RuntimeException("Failed to move file");
        }
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

    /**
     * @param StreamInterface $from
     * @param StreamInterface $to
     * @param bool $close
     * @return bool
     */
    protected function copyContents(StreamInterface $from, StreamInterface $to, bool $close = true): bool
    {
        if (!$from->isReadable() || !$to->isWritable()) {
            return false;
        }

        try {
            while (!$from->eof()) {
                $to->write($from->read(8192));
            }
        } catch (\Throwable $e) {
            return false;
        } finally {
            if ($close) {
                @$from->close();
                @$to->close();
            }
        }

        return true;
    }

    /**
     * @param array $file
     * @return self
     */
    public static function factory(array $file): self
    {
        return new self(
            $file['tmp_name'] ?? null,
            $file['name'] ?? null,
            $file['size'] ?? null,
            $file['type'] ?? null,
            $file['error'] ?? UPLOAD_ERR_OK
        );
    }

    /**
     * @param array $files
     * @return array|self[]|self[][]
     */
    public static function parseFiles(array $files): array
    {
        $list = [];

        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFileInterface) {
                $list[$key] = $file;
                continue;
            }

            if (!$file || !is_array($file)) {
                continue;
            }

            if (isset($file['tmp_name'])) {
                if (!is_array($file['tmp_name'])) {
                    $list[$key] = self::factory($file);
                    continue;
                }


                $nested = [];
                foreach ($file['tmp_name'] as $index => $name) {
                    $nested[$index] = [
                        'tmp_name' => $name,
                        'size' => $file['size'][$index] ?? null,
                        'error' => $file['error'][$index] ?? UPLOAD_ERR_OK,
                        'name' => $file['name'][$index] ?? null,
                        'type' => $file['type'][$index] ?? null
                    ];
                }

                $list[$key] = self::parseFiles($nested);
                continue;
            }

            $list[$key] = self::parseFiles($file);
        }

        return $list;
    }
}