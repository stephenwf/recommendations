<?php

namespace tests\eLife\Web;

use DateTimeImmutable;
use eLife\Recommendations\Relationships\ManyToManyRelationship;

/**
 * THIS IS NOT FINAL AND WILL BE REMOVED.
 *
 * @group web
 */
class BasicArticleRelationsTest extends WebTestCase
{
    public function testAddingRule()
    {
        $article1 = $this->addArticlePoAWithId('1', (new DateTimeImmutable())->setDate(2017, 1, 1), false);
        $article2 = $this->addArticlePoAWithId('2', (new DateTimeImmutable())->setDate(2017, 2, 2), false);

        $this->getRulesProcess()->import($article1);
        $this->getRulesProcess()->import($article2);

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/article/1');
        $json = $this->getJsonResponse();

        $this->assertEquals(1, $json->total);
        $this->assertEquals('2', $json->items[0]->id);

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/article/2');
        $json = $this->getJsonResponse();

        $this->assertEquals(1, $json->total);
        $this->assertEquals('1', $json->items[0]->id);
    }

    public function testTwoArticlesCanRelate()
    {
        $article1 = $this->addArticlePoAWithId('1');
        $article2 = $this->addArticlePoAWithId('2');

        $this->addRelation(new ManyToManyRelationship($article1, $article2));

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/article/2');
        $json = $this->getJsonResponse();

        $this->assertEquals(1, $json->total);
        $this->assertEquals('1', $json->items[0]->id);

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/article/1');
        $json = $this->getJsonResponse();

        $this->assertEquals(1, $json->total);
        $this->assertEquals('2', $json->items[0]->id);
    }

    public function testThreeArticlesCanRelateInOrder()
    {
        $article1 = $this->addArticlePoAWithId('1', (new DateTimeImmutable())->setDate(2017, 1, 1));
        $article2 = $this->addArticlePoAWithId('2', (new DateTimeImmutable())->setDate(2017, 2, 2));
        $article3 = $this->addArticlePoAWithId('3', (new DateTimeImmutable())->setDate(2017, 3, 3));

        $this->addRelation(new ManyToManyRelationship($article1, $article2));
        $this->addRelation(new ManyToManyRelationship($article1, $article3));

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/article/1');
        $json = $this->getJsonResponse();

        $this->assertEquals(2, $json->total);
        $this->assertEquals('3', $json->items[0]->id);
        $this->assertEquals('2', $json->items[1]->id);
    }

    public function testAtLeastOneResult()
    {
        $this->addArticlePoAWithId('1', (new DateTimeImmutable())->setDate(2017, 1, 1));
        $this->addArticlePoAWithId('2', (new DateTimeImmutable())->setDate(2017, 2, 2));

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/article/1');
        $json = $this->getJsonResponse();

        $this->assertEquals(1, $json->total);
    }
}
