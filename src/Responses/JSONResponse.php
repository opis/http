<?php
/* ============================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Http\Responses;

class JSONResponse extends StringResponse
{
    /**
     * @param $json
     * @param int $status
     * @param array $headers
     * @param int $encodeOptions
     */
    public function __construct(
        $json,
        int $status = 200,
        array $headers = [],
        int $encodeOptions = JSON_UNESCAPED_SLASHES
    ) {

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json; charset=utf-8';
        }

        parent::__construct(json_encode($json, $encodeOptions), $status, $headers);
    }
}