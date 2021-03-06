<?php

namespace eLife\Api\Response\Common;

use DateTimeImmutable;
use eLife\Api\Response\ImageResponse;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\Subject;

trait ArticleFromModel
{
    public function __construct(
        string $id,
        string $title,
        string $type,
        string $impactStatement = null,
        string $titlePrefix = null,
        string $authorLine,
        DateTimeImmutable $published,
        DateTimeImmutable $statusDate,
        int $volume,
        int $version,
        int $issue = null,
        string $elocationId,
        string $doi = null,
        string $stage = null,
        string $pdf = null,
        array $subjects,
        ImageResponse $image = null
    ) {
        $this->title = $title;
        $this->titlePrefix = $titlePrefix;
        $this->authorLine = $authorLine;
        $this->id = $id;
        $this->type = $type;
        $this->impactStatement = $impactStatement;
        $this->statusDate = $statusDate;
        $this->volume = $volume;
        $this->version = $version;
        $this->issue = $issue;
        $this->elocationId = $elocationId;
        $this->doi = $doi;
        $this->stage = $stage;
        $this->pdf = $pdf;
        $this->subjects = $subjects;
        $this->image = $image;
        $this->published = $published;
    }

    public static function fromModel(ArticleVersion $article)
    {
        return new static(
            $article->getId(),
            $article->getTitle(),
            $article->getType(),
            $article instanceof ArticleVoR ? $article->getImpactStatement() : null,
            $article->getTitlePrefix(),
            $article->getAuthorLine(),
            $article->getPublishedDate(),
            $article->getStatusDate(),
            $article->getVolume(),
            $article->getVersion(),
            $article->getIssue(),
            $article->getElocationId(),
            $article->getDoi(),
            $article->getStage(),
            $article->getPdf(),
            $article->getSubjects()->map(function (Subject $subject) {
                return SubjectResponse::fromModel($subject);
            })->toArray(),
            $article instanceof ArticleVoR ? ImageResponse::fromModels($article->getBanner(), $article->getThumbnail()) : null
        );
    }
}
