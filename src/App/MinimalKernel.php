<?php

namespace eLife\App;

interface MinimalKernel
{
    public function withApp(callable $fn);

    public function run();
}
