<?php

namespace tests\eLife\Web;

use eLife\App\Console;
use eLife\App\Kernel;
use Psr\Log\NullLogger;
use Silex\WebTestCase as SilexWebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class WebTestCase extends SilexWebTestCase
{
    protected $isLocal;
    protected $console;
    /** @var Kernel */
    protected $kernel;
    /** @var Client */
    protected $api;

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

        $config['elastic_index'] = 'elife_test';

        return $this->modifyConfiguration($config);
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
        $this->kernel = new Kernel($this->createConfiguration());

        return $this->kernel->getApp();
    }

    public function setUp()
    {
        parent::setUp();
        $lines = $this->runCommand('generate:database');
        var_dump($lines);
//        $this->assertStringStartsWith('Created new index', $lines[0], 'Failed to run test during set up');
    }

    public function tearDown()
    {
        // Delete database somehow.
        parent::tearDown();
    }

    public function runCommand(string $command)
    {
        $log = $this->returnCallback(function ($message) use (&$logs) {
            $logs[] = $message;
        });
        $logs = [];
        $logger = $this->createMock(NullLogger::class);

        foreach (['debug', 'info', 'warning', 'critical', 'emergency', 'alert', 'log', 'notice', 'error'] as $level) {
            $logger
                ->expects($this->any())
                ->method($level)
                ->will($log);
        }

        $app = new Application();
        $this->kernel->withApp(function ($app) use ($logger) {
            // Bug with silex?
            unset($app['logger']);
            $app['logger'] = function () use ($logger) {
                return $logger;
            };
        });
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
