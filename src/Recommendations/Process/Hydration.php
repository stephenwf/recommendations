<?php
/**
 * Hydration.
 *
 * Given a list of "RuleModels" with Ids, Types and Datetimes it will order them
 * and fetch their full fields from the API SDK (with caching).
 *
 * The result will be passed on to the Serializer.
 */

namespace eLife\Recommendations\Process;

use Assert\Assertion;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\HasSubjects;
use eLife\Bus\Queue\SingleItemRepository;
use eLife\Recommendations\RuleModel;

final class Hydration
{
    private $cache = [];
    private $repo;
    private $sdk;

    public function __construct(ApiSdk $sdk, SingleItemRepository $repo)
    {
        $this->repo = $repo;
        $this->sdk = $sdk;
    }

    public function convertType(string $type): string
    {
        switch ($type) {
            case 'article':
            case 'correction':
            case 'editorial':
            case 'feature':
            case 'insight':
            case 'research-advance':
            case 'research-article':
            case 'research-exchange':
            case 'retraction':
            case 'registered-report':
            case 'replication-study':
            case 'short-report':
            case 'tools-resources':
                return 'article';
            default:
                return $type;
        }
    }

    public function hydrateOne(RuleModel $item)
    {
        return $this->repo->get($this->convertType($item->getType()), $item->getId());
    }

    public function extractRelatedFrom(RuleModel $model)
    {
        $model = $this->hydrateOne($model);
        if ($model instanceof HasSubjects) {
            $this->cache['subjects'] = $this->cache['subjects'] ?? [];
            /** @var $model ArticleVersion */
            foreach ($model->getSubjects() as $subjected) {
                $this->cache['subjects'][$subjected->getId()] = $subjected;
            }
        }

        if ($model instanceof ArticleVersion) {
            $this->cache['related-article'] = $this->cache['related-article'] ?? [];
            /** @var $model ArticleVersion */
            foreach ($this->sdk->articles()->getRelatedArticles($model->getId()) as $relatedArticle) {
                $this->cache[$relatedArticle->getType()][$relatedArticle->getId()] = $relatedArticle;
            }
        }
    }

    /**
     * @param array $rules
     *
     * @return array
     */
    public function hydrateAll(array $rules): array
    {
        Assertion::allIsInstanceOf($rules, RuleModel::class);
        $entities = array_map([$this, 'hydrateOne'], $rules);

        return $entities;
    }
}
