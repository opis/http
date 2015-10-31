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
use RuntimeException;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Http\HttpResponseInterface;


class File implements HttpResponseInterface
{
    /** @var string File path. */
    protected $filePath;
    
    /** @var array Options. */
    protected $options;


    /**
     * Constructor.
     * 
     * @access  public
     * @param   string  $file       File path
     * @param   array   $options    Options
     */

    public function __construct($file, array $options = array())
    {
        if(!file_exists($file) || !is_readable($file))
        {
            throw new RuntimeException(vsprintf('File %s is not readeable or not exist', array($file)));
        }
        
        $this->filePath = $file;
        
        $this->options = $options + array(
            'fileName'    => basename($file),
            'disposition'  => 'attachment',
            'contentType' => Mime::get($file),
        );
    }
    
    /**
     * Handle the response
     *
     * @param   \Opis\Http\Request  $request    Http request
     * @param   \Opis\Http\Response $response   Http response
     */

    public function handle(Request $request, Response $response)
    {
        $opt = $this->options;
        $response->contentType($opt['contentType']);
        $response->header('content-disposition', vsprintf('%s; filename="%s"', array($opt['disposition'], $opt['fileName'])));
        $response->sendHeaders();
        $file = $this->filePath;
        $response->body(function($request, $response) use($file){
            readfile($file);
        });
        $response->send();
    }
}
