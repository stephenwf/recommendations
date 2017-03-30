<?php
/**
 * The one Rule to ring them all.
 *
 * This class will take in a set of rules, run through them and return a
 * list of RuleModels.
 *
 * The result will be passed to "Hydration".
 */

namespace eLife\Recommendations\Process;

use BadMethodCallException;
use eLife\ApiSdk\Model\Model;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Logging\Monitoring;
use eLife\Recommendations\Relationships\NoRelationship;
use eLife\Recommendations\Rule;
use eLife\Recommendations\Rule\RuleModelLogger;
use eLife\Recommendations\RuleModel;
use Psr\Log\LoggerInterface;

final class Rules
{
    use RuleModelLogger;
    private $rules;
    private $logger;
    private $monitoring;

    public function __construct(Monitoring $monitoring, LoggerInterface $logger, Rule ...$rules)
    {
        $this->rules = $rules;
        $this->logger = $logger;
        $this->monitoring = $monitoring;
    }

    public function isSupported(RuleModel $model, Rule $rule)
    {
        return in_array($model->getType(), $rule->supports());
    }

    public function getRuleModelFromSdk(Model $model, string $type = null)
    {
        if ($type === null) {
            $type = method_exists($model, 'getType') ? $model->getType() : null;
        }
        $ruleModel = null;
        if ($model instanceof PodcastEpisode) {
            // Import podcast.
            $ruleModel = new RuleModel($model->getNumber(), 'podcast-episode', $model->getPublishedDate());
            $this->debug($ruleModel, sprintf('Found episode %d', $model->getNumber()));
        } elseif (method_exists($model, 'getId') && $type) {
            $published = method_exists($model, 'getPublishedDate') ? $model->getPublishedDate() : null;
            // Import et al.
            $ruleModel = new RuleModel($model->getId(), $type, $published);
            $this->debug($ruleModel, sprintf('Identified rule model', $type, $model->getId()));
        } else {
            // Not good, not et al.
            $this->logger->alert('Unknown model type', [
                'model' => $model,
                'type' => $type,
            ]);
        }

        return $ruleModel;
    }

    public function import(RuleModel $model, bool $upsert = true, bool $prune = false): array
    {
        $this->monitoring->nameTransaction("Importing {$model->getType()}<{$model->getId()}>");
        $this->monitoring->startTransaction();
        if ($upsert === false && $prune === true) {
            throw new BadMethodCallException('You must upsert in order to prune.');
        }
        $all = [];
        foreach ($this->rules as $rule) {
            if ($this->isSupported($model, $rule) === false) {
                $this->debug($model, sprintf('Skipping import for rule %s', get_class($rule)));
                continue;
            }
            $relations = $rule->resolveRelations($model);
            if (!empty($relations)) {
                $this->debug($model, sprintf('Found %d relation(s) on model', count($relations)), [
                    'relations' => $relations,
                ]);
            } else {
                $this->debug($model, 'No relations in rule found, upserting');
                $relations = [new NoRelationship($model)];
            }
            if ($upsert) {
                array_map([$rule, 'upsert'], $relations);
            }
            if ($prune) {
                $rule->prune($model, $relations);
            }
            $all = array_merge($all, $relations);
        }
        $this->debug($model, sprintf('Total of %d relation(s) found', count($all)), [
            'relations' => $all,
        ]);
        $this->monitoring->endTransaction();

        return $all;
    }

    public function getRecommendations(RuleModel $model)
    {
        $next = [];
        foreach ($this->rules as $rule) {
            $prev = $next;
            $this->debug($model, sprintf('Starting rule process %s', get_class($rule)));
            $next = $rule->addRelations($model, $prev);
        }

        return $next;
    }
}
