<?php
/* ===========================================================================
 * Copyright 2018 The Opis Project
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

namespace Opis\Http\Traits;

trait HeadersTrait
{
    protected $headers = [];

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$this->formatHeader($name)]);
    }

    /**
     * @param string $name
     * @param string|null $default
     * @return null|string
     */
    public function getHeader(string $name, string $default = null): ?string
    {
        return $this->headers[$this->formatHeader($name)] ?? $default;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @param array|null $target
     */
    private function fillHeaders(array $headers, array &$target = null): void
    {
        if ($target === null) {
            $target = &$this->headers;
        }

        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || !is_string($name)) {
                continue;
            }
            $name = $this->formatHeader($name);
            $target[$name] = trim($value);
        }
    }

    /**
     * @param string $header
     * @return string
     */
    private function formatHeader(string $header): string
    {
        return ucwords(strtolower(trim($header)), '-');
    }
}