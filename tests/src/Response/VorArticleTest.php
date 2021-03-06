<?php

namespace eLife\Tests\Response;

use eLife\ApiSdk\Model\ArticleVoR;
use eLife\Recommendations\Response\VorArticle;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Builder;

final class VorArticleTest extends PHPUnit_Framework_TestCase
{
    public function test_article_can_be_build_from_model()
    {
        $builder = Builder::for(ArticleVoR::class);
        /** @var ArticleVoR $vorArticle */
        $vorArticle = $builder->create(ArticleVoR::class)->__invoke();
        VorArticle::fromModel($vorArticle);
    }
}
