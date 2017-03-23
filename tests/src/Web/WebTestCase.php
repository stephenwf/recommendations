<?php

namespace tests\eLife\Web;

use Doctrine\DBAL\Connection;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\Model;
use eLife\App\Console;
use eLife\App\Kernel;
use eLife\Bus\Queue\SingleItemRepository;
use eLife\Recommendations\Process\Hydration;
use eLife\Recommendations\Relationships\ManyToManyRelationship;
use eLife\Recommendations\RuleModel;
use eLife\Recommendations\RuleModelRepository;
use Psr\Log\NullLogger;
use Silex\WebTestCase as SilexWebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use test\eLife\ApiSdk\Builder;

abstract class WebTestCase extends SilexWebTestCase
{
    protected $isLocal;
    protected $console;
    /** @var Kernel */
    protected $kernel;
    /** @var Client */
    protected $api;
    private $itemMocks = [];
    private $transformer;

    public function getAllMocks()
    {
        return $this->itemMocks;
    }

    public function addArticlePoAWithId($id, $date = null)
    {
        $builder = Builder::for(ArticlePoA::class);
        /** @var ArticlePoA $PoaArticle */
        $PoaArticle = $builder->create(ArticlePoA::class)
            ->withId($id);
        if ($date) {
            $PoaArticle = $PoaArticle->withPublished($date);
        }
        $PoaArticle = $PoaArticle->__invoke();

        $this->addDocument('article', $id, $PoaArticle);

        $model = new RuleModel($id, 'research-article', $PoaArticle->getPublishedDate());
        $this->getRulesRepo()->upsert($model);

        return $model;
    }

    public function addDocument(string $type, string $id, Model $content)
    {
        $this->itemMocks[$type] = $this->itemMocks[$type] ? $this->itemMocks[$type] : [];
        $this->itemMocks[$type][$id] = $content;
    }

    public function addRelation(ManyToManyRelationship $ruleModel)
    {
        $this->getRulesRepo()->addRelation($ruleModel);
    }

    public function getJsonResponse()
    {
        /** @var Response $response */
        $response = $this->getResponse();
        if (!$response->isOk()) {
            $this->fail('Response returned was not 200: '.$response->getContent());
        }
        $json = json_decode($response->getContent());

        return $json;
    }

    public function newClient()
    {
        return $this->api = static::createClient();
    }

    public function getResponse()
    {
        return $this->api->getResponse();
    }

    public function createConfiguration()
    {
        if (file_exists(__DIR__.'/../../../config/local.php')) {
            $this->isLocal = true;
            $config = include __DIR__.'/../../../config/local.php';
        } else {
            $this->isLocal = false;
            $config = include __DIR__.'/../../../config/ci.php';
        }

        $config['tables']['rules'] = 'Rules__test';
        $config['tables']['references'] = 'References__test';

        return $this->modifyConfiguration($config);
    }

    public function getDatabase(): Connection
    {
        return $this->kernel->getApp()['db'];
    }

    public function getRulesRepo(): RuleModelRepository
    {
        return $this->kernel->getApp()['rules.repository'];
    }

    public function modifyConfiguration($config)
    {
        return $config;
    }

    protected function mapHeaders($headers)
    {
        $httpHeaders = [];
        foreach ($headers as $key => $header) {
            $httpHeaders['HTTP_'.$key] = $headers[$key];
        }

        return $httpHeaders;
    }

    protected function jsonRequest(string $verb, string $endpoint, array $params = array(), array $headers = array())
    {
        $server = array_merge(array(
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ), $this->mapHeaders($headers));

        return $this->api->request(
            $verb,
            $endpoint,
            $params,
            [],
            $server
        );
    }

    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        $this->kernel = new Kernel($this->createConfiguration(), false);

        return $this->kernel->getApp();
    }

    public function setUp()
    {
        parent::setUp();
        $lines = $this->runCommand('generate:database');
//        $this->assertStringStartsWith('Database created successfully.', $lines[0], 'Failed to run test during set up');
    }

    public function tearDown()
    {
        $this->getDatabase()->query(sprintf('SET FOREIGN_KEY_CHECKS=%s', (int) false));
        // Delete database.
        $this->getDatabase()->getSchemaManager()->dropTable('References__test');
        $this->getDatabase()->getSchemaManager()->dropTable('Rules__test');
        $this->getDatabase()->query(sprintf('SET FOREIGN_KEY_CHECKS=%s', (int) true));
        parent::tearDown();
    }

    public function runCommand(string $command)
    {
        $log = $this->returnCallback(function ($message) use (&$logs) {
            $logs[] = $message;
        });
        $logs = [];
        $logger = $this->createMock(NullLogger::class);

        $transformerCallback = $this->returnCallback(function ($type, $id) {
            return $this->itemMocks[$type][$id] ?? null;
        });
        $transformer = $this->createMock(SingleItemRepository::class);

        $transformer
            ->expects($this->any())
            ->method('get')
            ->will($transformerCallback);

        $this->transformer = $transformer;

        foreach (['debug', 'info', 'warning', 'critical', 'emergency', 'alert', 'log', 'notice', 'error'] as $level) {
            $logger
                ->expects($this->any())
                ->method($level)
                ->will($log);
        }

        $app = new Application();
        $this->kernel->withApp(function ($app) use ($logger, $transformer) {
//            unset($app['logger']);
//            $app['logger'] = function () use ($logger) {
//                return $logger;
//            };
//            unset($app['hydration']);
//            $app['hydration'] = function() {
//                return $this->hydrator;
//            };
            unset($app['hydration.single_item_repository']);
            $app['hydration.single_item_repository'] = function () use ($transformer) {
                return $transformer;
            };
        });
        $this->kernel->setupFlow();
        $app->setAutoExit(false);
        $application = new Console($app, $this->kernel);
        $application->logger = $logger;

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        return $logs;
    }
}
