<?php

namespace eLife\Recommendations\Rule;

use eLife\ApiSdk\Model\Article;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ExternalArticle;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Recommendations\Relationships\ManyToManyRelationship;
use eLife\Recommendations\Rule;
use eLife\Recommendations\Rule\Common\MicroSdk;
use eLife\Recommendations\Rule\Common\PersistRule;
use eLife\Recommendations\Rule\Common\RepoRelations;
use eLife\Recommendations\RuleModel;
use eLife\Recommendations\RuleModelRepository;

class PodcastEpisodeContents implements Rule
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

    public function get(RuleModel $input)
    {
        return $this->sdk->get($input->getType(), $input->getId());
    }

    public function resolveRelations(RuleModel $input): array
    {
        /** @var PodcastEpisode $model Added to stop IDE complaining. */
        $model = $this->get($input);
        $relations = [];
        foreach ($model->getChapters() as $chapter) {
            $content = $chapter->getContent();
            $relations[] = $content->filter(function ($content) {
                // Only article for now in this rule.
                return $content instanceof Article;
            })->map(function (ArticleVersion $article) use ($input, $chapter) {
                $type = $article instanceof ExternalArticle ? 'external-article' : $article->getType();
                $date = $article instanceof ArticleVersion ? $article->getPublishedDate() : null;
                // Link this podcast TO the related item.
                return new ManyToManyRelationship(
                    new RuleModel($article->getId(), $type, $date),
                    new RuleModel("{$input->getId()}-{$chapter->getNumber()}", 'podcast-episode-chapter', null, true)
                );
            })->toArray();
        }

        return array_reduce($relations, 'array_merge', []);
    }

    public function supports(): array
    {
        return [
            'podcast-episode',
            'podcast-episode-chapter',
        ];
    }
}
