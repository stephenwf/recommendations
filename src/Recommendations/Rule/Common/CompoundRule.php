<?php

namespace eLife\Recommendations\Rule\Common;

use eLife\Recommendations\Rule;

interface CompoundRule extends Rule
{
    public function getRules(): array;
}
