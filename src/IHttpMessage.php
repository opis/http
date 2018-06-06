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

interface IHttpMessage
{
    /**
     * @return string
     */
    public function getProtocolVersion(): string;

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool;

    /**
     * @param string $name
     * @return null|string
     */
    public function getHeader(string $name): ?string;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return null|IStream
     */
    public function getBody(): ?IStream;

    /**
     * @param callable $callback
     * @return IHttpMessage
     */
    public function modify(callable $callback): self;

    /**
     * @param string $version
     * @return IHttpRequest
     */
    public function withProtocolVersion(string $version): self;

    /**
     * @param string $name
     * @param string $value
     * @return IHttpRequest
     */
    public function withHeader(string $name, string $value): self;

    /**
     * @param array $headers
     * @return IHttpRequest
     */
    public function withHeaders(array $headers): self;

    /**
     * @param string $name
     * @return IHttpMessage
     */
    public function withoutHeader(string $name): self;

    /**
     * @param string ...$names
     * @return IHttpMessage
     */
    public function withoutHeaders(string ...$names): self;

    /**
     * @param null|IStream $body
     * @return IHttpRequest
     */
    public function withBody(?IStream $body): self;
}