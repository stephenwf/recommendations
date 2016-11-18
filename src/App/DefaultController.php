<?php

namespace eLife\App;

use Symfony\Component\HttpFoundation\Response;

final class DefaultController
{
    public function indexAction()
    {
        return new Response('eLife Recommendations!');
    }
}
