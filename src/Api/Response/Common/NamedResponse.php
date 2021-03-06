<?php

namespace eLife\Api\Response\Common;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

abstract class NamedResponse
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $id;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $type;

    /**
     * @Type("array<string, string>")
     */
    public $name;
}
