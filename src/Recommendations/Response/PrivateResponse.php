<?php

namespace eLife\Recommendations\Response;

use Symfony\Component\HttpFoundation\Response;

final class PrivateResponse extends Response
{
    public function __construct($content = '', $status = 200, array $headers = array())
    {
        parent::__construct($content, $status, $headers);
        $this->headers->set('Cache-Control', 'must-revalidate, no-cache, no-store, private');
    }
}
