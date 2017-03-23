<?php

namespace eLife\Recommendations\Relationships;

use eLife\Recommendations\Relationship;
use eLife\Recommendations\RuleModel;

class NoRelationship implements Relationship
{
    private $on;

    public function __construct(RuleModel $ruleModel)
    {
        $this->on = $ruleModel;
    }

    /**
     * Returns the owning side of the relationship.
     */
    public function getOn(): RuleModel
    {
        return $this->on;
    }

    /**
     * Returns the target of the relationship to be placed on the owning side.
     */
    public function getSubject(): RuleModel
    {
        return;
    }

    public function __toString()
    {
        return json_encode($this);
    }

    public function jsonSerialize()
    {
        return [
            'on' => $this->on,
            'subject' => null,
        ];
    }
}
