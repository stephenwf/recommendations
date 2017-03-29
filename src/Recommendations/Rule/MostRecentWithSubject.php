<?php

namespace eLife\Recommendations\Rule;

use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\HasSubjects;
use eLife\ApiSdk\Model\Subject;
use eLife\Bus\Queue\SingleItemRepository;
use eLife\Recommendations\Relationships\ManyToManyRelationship;
use eLife\Recommendations\Rule;
use eLife\Recommendations\Rule\Common\MicroSdk;
use eLife\Recommendations\Rule\Common\PersistRule;
use eLife\Recommendations\RuleModel;
use eLife\Recommendations\RuleModelRepository;
use LogicException;
use Psr\Log\LoggerInterface;

class MostRecentWithSubject implements Rule
{
    use PersistRule;

    private $sdk;
    private $repo;
    private $logger;
    private $singleItemRepository;

    public function __construct(
        SingleItemRepository $singleItemRepository,
        MicroSdk $sdk,
        RuleModelRepository $repo,
        LoggerInterface $logger
    ) {
        $this->sdk = $sdk;
        $this->repo = $repo;
        $this->logger = $logger;
        $this->singleItemRepository = $singleItemRepository;
    }

    public function getType($type)
    {
        switch ($type) {
            case 'blog-article':
            case 'event':
            case 'labs-experiment':
            case 'interview':
            case 'podcast-episode':
            case 'collection':
                return $type;
                break;

            // are these needed?
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
                // end of -- are these needed?
            case 'article':
                return 'article';
                break;

            default:
                throw new LogicException('ApiSDK does not exist for provided type: '.$type);
        }
    }

    public function get(RuleModel $input)
    {
        return $this->sdk->get($this->getType($input->getType()), $input->getId());
    }

    public function resolveRelations(RuleModel $input): array
    {
        /** @var HasSubjects $model Added to stop IDE complaining. */
        $model = $this->get($input);

        if (!$model instanceof HasSubjects) {
            $this->logger->debug($input->getType().'<'.$input->getId().'> has no subjects', [
                'model' => $model,
            ]);

            return [];
        }

        return $model
            ->getSubjects()
            ->map(function (Subject $subject) use ($input) {
                return new ManyToManyRelationship($input, new RuleModel($subject->getId(), 'subject', null, true));
            })
            ->toArray();
    }

    public function addRelations(RuleModel $model, array $list): array
    {
        /** @var ArticleVersion $article */
        $article = $this->singleItemRepository->get($this->getType($model->getType()), $model->getId());
        $subjects = $article->getSubjects();
        /** @var Subject $subject */
        $subject = $subjects[0] ?? null;
        // Nope out early.
        if (!$subject) {
            return $list;
        }
        foreach ($this->repo->getLatestArticleWithSubject($subject->getId()) as $item) {
            /* @var RuleModel $item */
            $intersection = array_filter($list, function (RuleModel $listItem) use ($item) {
                return $listItem->equalTo($item);
            });
            if (
                empty($intersection) &&
                $item->equalTo($model) === false
            ) {
                array_push($list, $item);

                return $list;
            }
        }
        $this->logger->error('Latest article with subject `'.$subject->getId().'` could not be found', [
            'model' => $model,
        ]);

        return $list;
    }

    protected function getRepository(): RuleModelRepository
    {
        return $this->repo;
    }

    public function supports(): array
    {
        return [
            'collection',
            'podcast-episode',
            'correction',
            'editorial',
            'feature',
            'insight',
            'research-advance',
            'research-article',
            'research-exchange',
            'retraction',
            'registered-report',
            'replication-study',
            'short-report',
            'tools-resources',
        ];
    }
}
