<?php

namespace eLife\Recommendations\Rule;

use eLife\ApiSdk\Model\Article;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\Collection;
use eLife\ApiSdk\Model\ExternalArticle;
use eLife\Recommendations\Relationships\ManyToManyRelationship;
use eLife\Recommendations\Rule;
use eLife\Recommendations\Rule\Common\MicroSdk;
use eLife\Recommendations\Rule\Common\PersistRule;
use eLife\Recommendations\Rule\Common\RepoRelations;
use eLife\Recommendations\RuleModel;
use eLife\Recommendations\RuleModelRepository;

final class CollectionContents implements Rule
{
    use PersistRule;
    use RepoRelations;

    private $sdk;
    private $repo;

    public function __construct(MicroSdk $sdk, RuleModelRepository $repo)
    {
        $this->sdk = $sdk;
        $this->repo = $repo;
    }

    public function getCollection(string $id)
    {
        return $this->sdk->get('collection', $id);
    }

    public function resolveRelations(RuleModel $input): array
    {
        if ($input->getType() !== 'collection') {
            return [];
        }
        $collection = $this->getCollection($input->getId());

        return $collection->getContent()
            ->filter(function ($item) {
                return $item instanceof Article;
            })
            ->map(function (Article $article) use ($input) {
                $type = $article instanceof ExternalArticle ? 'external-article' : 'research-article';
                $date = $article instanceof ArticleVersion ? $article->getPublishedDate() : null;
                // Add collection TO article.
                return new ManyToManyRelationship(new RuleModel($article->getId(), $type, $date), $input);
            })
            ->toArray();
    }

    public function supports(): array
    {
        return [
            'collection',
        ];
    }
}
