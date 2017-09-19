<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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


class ResponseHandler
{
    /** @var  Request */
    protected $request;

    /** @var  int[] */
    protected $nobody;

    /**
     * ResponseHandler constructor.
     * @param Request $request
     * @param null|int[] $nobody
     */
    public function __construct(Request $request, $nobody = null)
    {
        if($nobody === null){
            $nobody = [100, 101, 102, 204, 205, 304];
        }

        $this->nobody = $nobody;
        $this->request = $request;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Generate response
     *
     * @param Response $response
     */
    public function sendResponse(Response $response)
    {
        $body = $response->getBody();

        if(in_array($response->getStatusCode(), $this->nobody)){
            $response->deleteHeader('Content-Length');
            $body = null;
        }

        if($body instanceof \Closure){
            $body($response, $this);
            return;
        } elseif ($body === null){
            $this->sendHeaders($response);
            return;
        }

        $content = (string) $body;

        $response->addHeader('Content-Length', strlen($content));

        $this->sendHeaders($response);

        echo $content;
    }

    /**
     * Send headers
     *
     * @param Response $response
     */
    public function sendHeaders(Response $response)
    {
        if(headers_sent()) {
            return;
        }

        if($this->request->server('FCGI_SERVER_VERSION', false) !== false) {
            $protocol = 'Status:';
        } else {
            $protocol = $this->request->server('SERVER_PROTOCOL', 'HTTP/1.1');
        }

        header($protocol . ' ' . $response->getStatusCode() . ' ' . $response->getStatusCodeMessage());

        if(!$response->hasHeader('Content-Type')){
            $contentType = $response->getContentType();
            if(stripos($contentType, 'text/') === 0 ||
                in_array($contentType, array('application/json', 'application/xml', 'application/xhtml+xml')))
            {
                $contentType .= '; charset=' . $response->getCharset();
            }
            $response->addHeader('Content-Type', $contentType);
        }

        foreach ($response->getHeaders() as $name => $value){
            header($name . ':' . $value);
        }

        foreach ($response->getCookies() as $cookie){
            setcookie($cookie['name'], $cookie['value'], $cookie['ttl'], $cookie['path'],
                $cookie['domain'], $cookie['secure'], $cookie['httponly']);
        }
    }
}