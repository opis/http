<?php
/* ============================================================================
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

namespace Opis\Http\Response;

class HtmlResponse extends StringResponse
{
    /**
     * HtmlResponse constructor.
     * @param $body
     * @param int $status
     * @param array|null $headers
     */
    public function __construct($body, int $status = 200, array $headers = null) {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html';
        }
        $body = $status === 204 ? '' : (string) $body;
        parent::__construct($body, $status, $headers);
    }
}