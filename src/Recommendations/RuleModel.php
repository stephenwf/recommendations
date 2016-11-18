<?php

namespace eLife\Recommendations;

interface RuleModel
{
    /**
     * Synthetic check
     *
     * This will return whether or not the item is retrievable from
     * the API SDK. If it is synthetic, the data will have to be
     * retrieved from another, local, data source.
     */
    public function isSynthetic() : bool;

    /**
     * Returns the ID or Number of item.
     */
    public function getId() : string;

    /**
     * Returns the type of item.
     */
    public function getType() : string;
}
