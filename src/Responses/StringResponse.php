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

namespace Opis\Http\Responses;

use Opis\Http\Response;
use Opis\Stream\ResourceStream;

class StringResponse extends Response
{
    /**
     * @param string $body
     * @param int $status
     * @param array $headers
     */
    public function __construct(string $body, int $status = 200, array $headers = [])
    {
        $len = strlen($body);

        if ($len) {
            $headers['Content-Length'] = (string)$len;
            $stream = new ResourceStream("php://temp", "wb+");
            $stream->write($body);
            $stream->rewind();
        } else {
            unset($headers['Content-Length']);
            $stream = null;
        }

        parent::__construct($status, $headers, $stream);
    }
}