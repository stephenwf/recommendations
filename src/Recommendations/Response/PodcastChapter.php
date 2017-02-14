<?php

namespace eLife\Recommendations\Response;

use eLife\ApiSdk\Model\PodcastEpisodeChapter;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class PodcastChapter
{
    /**
     * @Type("integer")
     * @Since(version="1")
     */
    private $number;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    private $title;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    private $time;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    private $impactStatement;

    public function __construct(
        int $number,
        string $title,
        int $time,
        string $impactStatement = null
    ) {
        $this->number = $number;
        $this->title = $title;
        $this->time = $time;
        $this->impactStatement = $impactStatement;
    }

    public static function fromModel(PodcastEpisodeChapter $chapter)
    {
        return new static(
            $chapter->getNumber(),
            $chapter->getTitle(),
            $chapter->getTime(),
            $chapter->getImpactStatement()
        );
    }
}
