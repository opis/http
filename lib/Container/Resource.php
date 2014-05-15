<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

namespace Opis\Http\Container;

use RuntimeException;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Http\Mime;
use Opis\Http\ResponseContainerInterface;


class Resource implements ResponseContainerInterface
{

    /** @var string File path. */
    protected $filePath;

    /**
     * Constructor.
     * 
     * @access  public
     * @param   string  $file       File path
     * @param   array   $options    Options
     */

    public function __construct($file)
    {
        if(file_exists($file) === false || is_readable($file) === false)
        {
            throw new RuntimeException(vsprintf("%s(): File [ %s ] is not readable.", array(__METHOD__, $file)));
        }
        $this->filePath = $file;
    }


    /**
     * Sends the response.
     * 
     * @access  public
     * @param   \Opis\Http\Request
     * @param   \Opis\Http\Response
     */

    public function send(Request $request, Response $response)
    {
        $response->type(Mime::get($this->filePath));
        $response->header('accept-ranges', 'bytes');
        $response->sendHeaders();
        $response->body(file_get_contents($this->filePath));
        $response->send();
    }
}
