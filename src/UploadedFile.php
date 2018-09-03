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

namespace Opis\Http;

use InvalidArgumentException, RuntimeException;

class UploadedFile implements IUploadedFile
{
    /** @var string */
    protected $name;

    /** @var string|null */
    protected $type;

    /** @var int */
    protected $error;

    /** @var int|null */
    protected $size;

    /** @var IStream|null */
    protected $stream = null;

    /** @var null|string */
    protected $file = null;

    /** @var bool */
    protected $moved = false;

    /**
     * @param string|resource|IStream $file
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

        if (is_string($file)) {
            if (!is_file($file)) {
                throw new RuntimeException("File {$file} does not exists");
            }
            if (substr(PHP_SAPI, 0, 3) !== 'cli' && !is_uploaded_file($file)) {
                throw new RuntimeException("File {$file} was not uploaded");
            }

            $this->file = realpath($file);
            if ($size === null) {
                $size = filesize($file);
            }
        } elseif (is_resource($file)) {
            $this->stream = new Stream($file, 'r');
            if ($size === null) {
                $size = $this->stream->getSize();
            }

        } elseif ($file instanceof IStream) {
            $this->stream = $file;
            if ($size === null) {
                $size = $this->stream->getSize();
            }
        } else {
            throw new InvalidArgumentException("Invalid value for file");
        }

        $this->size = $size;
    }

    /**
     * @inheritDoc
     */
    public function getStream(): IStream
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
    public function moveToFile(string $destination): bool
    {
        if ($this->moved) {
            throw new RuntimeException("File was already moved");
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("File was not properly uploaded");
        }

        $targetDir = dirname($destination);
        if (!is_dir($targetDir)) {
            throw new RuntimeException("Directory {$targetDir} does not exists");
        }
        if (!is_writable($targetDir)) {
            throw new RuntimeException("Directory {$targetDir} is not writable");
        }

        $ok = false;

        if ($this->file !== null) {
            if (substr(PHP_SAPI, 0, 3) === 'cli') {
                $ok = rename($this->file, $destination);
            } else {
                $ok = is_uploaded_file($this->file) && move_uploaded_file($this->file, $destination);
            }
        } elseif ($this->stream !== null) {
            $ok = $this->copyContents($this->stream, new Stream(realpath($destination), 'wb'));
        }

        if ($ok) {
            $this->moved = true;
        }

        return $ok;
    }

    /**
     * @inheritDoc
     */
    public function wasMoved(): bool
    {
        return $this->moved;
    }

    /**
     * @inheritDoc
     */
    public function moveToStream(IStream $destination): bool
    {
        if ($this->moved) {
            throw new RuntimeException("File was already moved");
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("File was not properly uploaded");
        }

        if (!$destination->isWritable()) {
            throw new InvalidArgumentException("Stream is not writable");
        }

        if ($this->copyContents($this->getStream(), $destination)) {
            $this->moved = true;
            return true;
        }

        return false;
    }


    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    /**
     * @param IStream $from
     * @param IStream $to
     * @param bool $close
     * @return bool
     */
    protected function copyContents(IStream $from, IStream $to, bool $close = true): bool
    {
        if (!$from->isReadable() || !$to->isWritable()) {
            return false;
        }

        try {
            while (!$from->eof()) {
                $to->write($from->read());
            }
        } catch (\Throwable $e) {
            return false;
        } finally {
            if ($close) {
                @$from->close();
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
            if ($file instanceof IUploadedFile) {
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
                        'type' => $file['type'][$index] ?? null,
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