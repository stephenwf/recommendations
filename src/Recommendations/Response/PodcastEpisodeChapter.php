<?php

namespace eLife\Recommendations\Response;

use eLife\ApiSdk\Model\PodcastEpisodeChapterModel;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class PodcastEpisodeChapter implements Result
{
    /**
     * @Type(PodcastChapter::class)
     * @Since(version="1")
     */
    public $chapter;

    /**
     * @Type(PodcastEpisode::class)
     * @Since(version="1")
     */
    public $episode;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type = 'podcast-episode-chapter';

    public function getType(): string
    {
        return $this->type;
    }

    public function __construct(
        PodcastEpisode $episode,
        PodcastChapter $chapter
    ) {
        $this->episode = $episode;
        $this->chapter = $chapter;
    }

    public static function fromModel(PodcastEpisodeChapterModel $model)
    {
        return new static(
            PodcastEpisode::fromModel($model->getEpisode()),
            PodcastChapter::fromModel($model->getChapter())
        );
    }
}
