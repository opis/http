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

interface IUploadedFile
{
    /**
     * @return IStream
     */
    public function getStream(): IStream;

    /**
     * @param string $path
     */
    public function moveTo(string $path): void;

    /**
     * @return int|null
     */
    public function getSize(): ?int;

    /**
     * @return int
     */
    public function getError(): int;

    /**
     * @return null|string
     */
    public function getClientFilename(): ?string;

    /**
     * @return null|string
     */
    public function getClientMediaType(): ?string;
}