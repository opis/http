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

namespace Opis\Http\Response;

use Opis\Http\{
    MimeType, Response, Stream
};

class FileDownload extends Response
{
    public function __construct(string $file, array $options = [], int $statusCode = 200, array $headers = [])
    {
        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf('File %s does not exist', $file));
        }

        $options += [
            'file_name' => basename($file),
            'disposition' => 'attachment',
            'content_type' => MimeType::get($file),
        ];

        $o = $options;

        $headers['Content-Type'] = $o['content_type'];
        $headers['Content-Length'] = filesize($file);
        $headers['Content-Dispsition'] = sprintf('%s; filename="%s"', $o['disposition'], $o['file_name']);

        $body = new Stream(fopen($file, 'r'));

        parent::__construct($statusCode, $headers, $body);
    }
}