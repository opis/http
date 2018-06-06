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


interface IUri
{
    /**
     * @return string
     */
    public function getScheme(): ?string;

    /**
     * @return string
     */
    public function getAuthority(): ?string;

    /**
     * @return string
     */
    public function getUserInfo(): ?string;

    /**
     * @return string
     */
    public function getHost(): ?string;

    /**
     * @return int|null
     */
    public function getPort(): ?int;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return string
     */
    public function getQuery(): ?string;

    /**
     * @return string
     */
    public function getFragment(): ?string;

    /**
     * @return array
     */
    public function getComponents(): array;

    /**
     * @param callable $callback
     * @return IUri
     */
    public function modify(callable $callback): self;

    /**
     * @param string $scheme
     * @return IUri
     */
    public function withScheme(string $scheme): self;

    /**
     * @param string $user
     * @param string|null $password
     * @return IUri
     */
    public function withUserInfo(string $user, string $password = null): self;

    /**
     * @param string $host
     * @return IUri
     */
    public function withHost(string $host): self;

    /**
     * @param int|null $port
     * @return IUri
     */
    public function withPort(?int $port): self;

    /**
     * @param string $path
     * @return IUri
     */
    public function withPath(string $path): self;

    /**
     * @param string $query
     * @return IUri
     */
    public function withQuery(string $query): self;

    /**
     * @param string $fragment
     * @return IUri
     */
    public function withFragment(string $fragment): self;

    /**
     * @return string
     */
    public function __toString();
}