<?php

namespace tests\eLife\Rule;

use eLife\ApiSdk\Model\PodcastEpisode as PodcastEpisodeModel;
use eLife\Recommendations\Rule\PodcastEpisodeContents;
use eLife\Recommendations\RuleModel;
use test\eLife\ApiSdk\Serializer\PodcastEpisodeNormalizerTest;

class PodcastEpisodeContentsTest extends BaseRuleTest
{
    /**
     * @dataProvider getPodcastData
     */
    public function test(PodcastEpisodeModel $podcastEpisode)
    {
        /** @var PodcastEpisodeContents | \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->createPartialMock(PodcastEpisodeContents::class, ['get']);
        $mock->expects($this->exactly(1))
            ->method('get')
            ->willReturn($podcastEpisode);

        $relations = $mock->resolveRelations(new RuleModel('1', 'article'));
        foreach ($relations as $relation) {
            $this->assertValidRelation($relation);
        }
    }

    public function getPodcastData()
    {
        return (new PodcastEpisodeNormalizerTest())->normalizeProvider();
    }
}
