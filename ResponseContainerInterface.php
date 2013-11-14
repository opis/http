<?php

namespace Opis\Http;

interface ResponseContainerInterface
{
    function send(Request $request, Response $response);
}