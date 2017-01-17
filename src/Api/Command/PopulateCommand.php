<?php
/**
 * Populate command.
 *
 * For populating the database initially.
 */

namespace eLife\Api\Command;

use eLife\ApiSdk\ApiSdk;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Traversable;

abstract class PopulateCommand extends Command
{
    private $sdk;
    private $serializer;
    private $output;
    protected $logger;

    public function __construct(
        ApiSdk $sdk,
        LoggerInterface $logger
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->logger = $logger;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('api:import')
            ->setDescription('Import items from API.')
            ->setHelp($this->getCommandHelp())
            ->addArgument('entity', InputArgument::REQUIRED, 'Must be one of the following <comment>['.implode(', ', self::getSupportedModels()).']</comment>');
    }

    abstract protected function getCommandHelp() : string;

    protected static function getSupportedModels()
    {
        return ['all', 'BlogArticles', 'Events', 'Interviews', 'LabsExperiments', 'PodcastEpisodes', 'Collections', 'ResearchArticles'];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $entity = $input->getArgument('entity');
        // Only the configured.
        if (!in_array($entity, static::getSupportedModels())) {
            $this->logger->error('Entity with name '.$entity.' not supported.');

            return;
        }
        if ($entity === 'all') {
            foreach (static::getSupportedModels() as $e) {
                if ($e !== 'all') {
                    // Run the item.
                    $this->{'import'.$e}();
                }
            }
        } else {
            // Run the item.
            $this->{'import'.$entity}();
        }
        // Reporting.
        $this->logger->info("\nAll entities queued.");
    }

    public function importPodcastEpisodes()
    {
        $this->logger->info('Importing Podcast Episodes');
        $episodes = $this->sdk->podcastEpisodes();
        $this->iterateSerializeTask($episodes, 'podcast-episode');
    }

    public function importCollections()
    {
        $this->logger->info('Importing Collections');
        $collections = $this->sdk->collections();
        $this->iterateSerializeTask($collections, 'collection');
    }

    public function importLabsExperiments()
    {
        $this->logger->info('Importing Labs Experiments');
        $events = $this->sdk->labsExperiments();
        $this->iterateSerializeTask($events, 'labs-experiment');
    }

    public function importResearchArticles()
    {
        $this->logger->info('Importing Research Articles');
        $events = $this->sdk->articles();
        $this->iterateSerializeTask($events, 'research-article');
    }

    public function importInterviews()
    {
        $this->logger->info('Importing Interviews');
        $events = $this->sdk->interviews();
        $this->iterateSerializeTask($events, 'interview');
    }

    public function importEvents()
    {
        $this->logger->info('Importing Events');
        $events = $this->sdk->events();
        $this->iterateSerializeTask($events, 'event');
    }

    public function importBlogArticles()
    {
        $this->logger->info('Importing Blog Articles');
        $articles = $this->sdk->blogArticles();
        $this->iterateSerializeTask($articles, 'blog-article');
    }

    private function iterateSerializeTask(Traversable $items, string $type)
    {
        $progress = null;
        foreach ($items as $item) {
            try {
                $this->processModel($type, $item);
            } catch (Throwable $e) {
                $this->logger->alert($e->getMessage());
                $this->logger->warning('Skipping import on a '.get_class($item), ['exception' => $e]);
                continue;
            }
        }
    }

    abstract public function processModel(string $type, $model);
}
