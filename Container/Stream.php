<?php

namespace Opis\Http\Container;

use Closure;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Http\ResponseContainerInterface;


class Stream implements ResponseContainerInterface
{
    /** @var \Closure Stream. */
    protected $stream;
    
    /**
     * Constructor.
     * 
     * @access  public
     * @param   \Closure  $stream  Stream
     */

    public function __construct(Closure $stream)
    {
        $this->stream = $stream;
    }



    /**
     * Flushes a chunck of data.
     * 
     * @access  public
     * @param   string   $chunk       Chunck of data to flush
     * @param   boolean  $flushEmpty  (optional) Flush empty chunk?
     */

    public function flush($chunk, $flushEmpty = false)
    {
        if(!empty($chunk) || $flushEmpty === true)
        {
            printf("%x\r\n%s\r\n", strlen($chunk), $chunk);
            flush();
        }
    }

    /**
     * Sends the stream.
     * 
     * @access  protected
     */

    protected function flow()
    {
        // Erase output buffers and disable output buffering
        while(ob_get_level() > 0) ob_end_clean();
        // Send the stream
        $stream = $this->stream;
        $stream($this);
        // Send empty chunk to tell the client that we're done
        $this->flush(null, true);
    }

    /**
     * Sends the response.
     * 
     * @access  public
     * @param   \Ops\Http\Request
     * @param   \Opis\http\Response
     */

    public function send(Request $request, Response $response)
    {
        $response->header('transfer-encoding', 'chunked');
        $response->sendHeaders();
        $this->flow();
    }
}