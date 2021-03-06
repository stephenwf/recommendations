<?php

namespace eLife\Recommendations;

interface Rule
{
    /**
     * Resolve Relations.
     *
     * Given a model (type + id) from SQS, calculate which entities need relations added
     * for the specific domain rule.
     *
     * Return is an array of tuples containing an input and an on where `input` is the model to be
     * added and `on` is the target node. In plain english given a podcast containing articles it would
     * return an array where the podcast is every `input` and each article is the `output`.
     */
    public function resolveRelations(RuleModel $input) : array;

    /**
     * Upsert relations.
     *
     * Given an `input` and an `on` it will persist this relationship for retrieval
     * in recommendation results.
     */
    public function upsert(Relationship $relationship);

    /**
     * Prune relations.
     *
     * Given an `input` this will go through the persistence layer and remove old non-existent relation ships
     * for this given `input`. Its possible some logic will be shared with resolve relations, but this is up
     * to each implementation.
     *
     * Optionally gets passed the relationships returned from resolve relationships
     */
    public function prune(RuleModel $input, array $relationships = null);

    /**
     * Add relations for model to list.
     *
     * This will be what is used when constructing the recommendations. Given a model (id, type) we return an array
     * of [type, id]'s that will be hydrated into results by the application. The aim is for this function to be
     * as fast as possible given its executed at run-time.
     */
    public function addRelations(RuleModel $model, array $list) : array;

    /**
     * Returns item types that are supported by rule.
     */
    public function supports() : array;
}
