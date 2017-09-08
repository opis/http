<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013-2015 Marius Sarca
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

use Opis\Http\Mime;
use Opis\Http\ResponseHandler;
use RuntimeException;
use Opis\Http\Request;
use Opis\Http\Response;


class Resource extends Response
{
    public function __construct(string $file, string $contentType = null)
    {
        if(!file_exists($file) || !is_readable($file)) {
            throw new RuntimeException(vsprintf('File %s is not readable or not exist', [$file]));
        }

        if($contentType === null){
            $contentType = Mime::get($file);
        }

        $this->setContentType($contentType)
            ->addHeader('Content-Length', filesize($file));

        parent::__construct(function (Response $response, ResponseHandler $handler) use($file){
            $handler->sendHeaders($response);
            readfile($file);
        });
    }
}