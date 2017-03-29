<?php

namespace tests\eLife\Web;

use DateTimeImmutable;

/**
 * @group web
 */
class CacheHeadersTest extends WebTestCase
{
    public function testETag()
    {
        $this->addArticlePoAWithId('1', (new DateTimeImmutable())->setDate(2017, 1, 1));
        $this->addArticlePoAWithId('2', (new DateTimeImmutable())->setDate(2017, 2, 2));

        $this->newClient();
        $this->jsonRequest('GET', '/recommendations/research-article/1');
        $response = $this->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $eTag = $response->headers->get('ETag');
        $this->assertEquals('max-age=300, public, stale-if-error=86400, stale-while-revalidate=300', $response->headers->get('Cache-Control'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));

        $this->jsonRequest('GET', '/recommendations/research-article/1', [], ['If-None-Match' => $eTag]);
        $response = $this->getResponse();
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('max-age=300, public, stale-if-error=86400, stale-while-revalidate=300', $response->headers->get('Cache-Control'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));

        $this->jsonRequest('GET', '/recommendations/research-article/1', [], ['If-None-Match' => 'NOT REAL ETAG']);
        $response = $this->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('max-age=300, public, stale-if-error=86400, stale-while-revalidate=300', $response->headers->get('Cache-Control'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
    }

    public function modifyConfiguration($config)
    {
        $config['ttl'] = 300;
        $config['debug'] = false;

        return $config;
    }
}
