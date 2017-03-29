<?php

namespace tests\eLife\Rule;

use eLife\Recommendations\Relationships\ManyToManyRelationship;
use PHPUnit_Framework_TestCase;

abstract class BaseRuleTest extends PHPUnit_Framework_TestCase
{
    public function assertValidRelation(ManyToManyRelationship $relation)
    {
        /* @var ManyToManyRelationship $relation */
        $this->assertInstanceOf(ManyToManyRelationship::class, $relation);
        $this->assertNotNull($relation->getOn());
        $this->assertNotNull($relation->getOn()->getId());
        $this->assertNotNull($relation->getOn()->getType());
        $this->assertNotNull($relation->getSubject());
        $this->assertNotNull($relation->getSubject()->getId());
        $this->assertNotNull($relation->getSubject()->getType());
    }
}
