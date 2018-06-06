<?php
/* ===========================================================================
 * Copyright 2013-2018 The Opis Project
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

interface IStream
{
    public function close(): void;

    /**
     * @return resource|null
     */
    public function detach();

    /**
     * @return int|null
     */
    public function getSize(): ?int;

    /**
     * @return int
     */
    public function tell(): int;

    /**
     * @return bool
     */
    public function eof(): bool;

    /**
     * @return bool
     */
    public function isSeekable(): bool;

    /**
     * @param int $offset
     * @param int $whence
     */
    public function seek(int $offset, int $whence = SEEK_SET): void;

    /**
     * Perform seek(0)
     */
    public function rewind(): void;

    /**
     * @return bool
     */
    public function isWritable(): bool;

    /**
     * @param string $string
     * @return int
     */
    public function write(string $string): int;

    /**
     * @return bool
     */
    public function isReadable(): bool;

    /**
     * @param int $length
     * @return string
     */
    public function read(int $length): string;

    /**
     * @return string
     */
    public function getContents(): string;

    /**
     * @param string|null $key
     * @return mixed|array|null
     */
    public function getMetadata(string $key = null);

    /**
     * @return string
     */
    public function __toString();
}