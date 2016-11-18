<?php

namespace eLife\App;

use Closure;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class Console
{
    /**
     * These commands map to [name]Command so when the command "hello" is configured
     * it will call helloCommand() on this class with InputInterface and OutputInterface
     * as parameters.
     *
     * This will hopefully cover most things.
     */
    public static $quick_commands = [
        'hello' => ['description' => 'This is a quick hello world command'],
        'echo' => ['description' => 'Example of asking a question'],
        'cache:clear' => ['description' => 'Clears cache'],
        'debug:params' => ['description' => 'Lists current parameters'],
    ];

    public function __construct(Application $console, Kernel $app)
    {
        $this->console = $console;
        $this->app = $app;
        $this->root = __DIR__.'/../..';

        $this->console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
    }

    private function path($path = '')
    {
        return $this->root.$path;
    }

    public function debugParamsCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        foreach ($this->app->get('config') as $key => $config) {
            if (is_array($config)) {
                $logger->warning($key);
                $logger->info(json_encode($config, JSON_PRETTY_PRINT));
                $logger->debug(' ');
            } elseif (is_bool($config)) {
                $logger->warning($key);
                $logger->info($config ? 'true' : 'false');
                $logger->debug(' ');
            } else {
                $logger->warning($key);
                $logger->info($config);
                $logger->debug(' ');
            }
        }
    }

    public function cacheClearCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $logger->warning('Clearing cache...');
        try {
            exec('rm -rf '.$this->root.'/cache/*');
        } catch (Exception $e) {
            $logger->error($e);
        }
        $logger->info('Cache cleared successfully.');
    }

    public function echoCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $question = new Question('<question>Are we there yet?</question> ');
        $helper = new QuestionHelper();
        while (true) {
            $name = $helper->ask($input, $output, $question);
            if ($name === 'yes') {
                break;
            }
            $logger->error($name);
        }
    }

    public function helloCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $logger->info('Hello from the outside (of the global scope)');
        $logger->debug('This is working');
    }

    public function run()
    {
        foreach (self::$quick_commands as $name => $cmd) {
            if (strpos($name, ':')) {
                $pieces = explode(':', $name);
                $first = array_shift($pieces);
                $pieces = array_map('ucfirst', $pieces);
                array_unshift($pieces, $first);
                $fn = implode('', $pieces);
            } else {
                $fn = $name;
            }
            if (!method_exists($this, $fn.'Command')) {
                throw new LogicException('Your command does not exist: '.$fn.'Command');
            }
            // Hello
            $command = $this->console
                ->register($name)
                ->setDescription($cmd['description'] ?? $name.' command')
                ->setCode(Closure::bind(function (InputInterface $input, OutputInterface $output) use ($fn, $name) {
                    $logger = new CliLogger($input, $output);
                    $this->{$fn.'Command'}($input, $output, $logger);
                }, $this));

            if (isset($cmd['args'])) {
                foreach ($cmd['args'] as $arg) {
                    $command->addArgument($arg['name'], $arg['mode'] ?? null, $arg['description'] ?? '', $arg['default'] ?? null);
                }
            }
        }
        $this->console->run();
    }
}
