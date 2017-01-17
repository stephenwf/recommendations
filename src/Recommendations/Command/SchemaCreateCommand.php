<?php

namespace eLife\Recommendations\Command;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Schema;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SchemaCreateCommand extends Command
{
    private $db;
    private $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('schema:create')
            ->setDescription('Creates schema for recommendations database.')
            ->addOption('drop', 'd', InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $schema = new Schema();
        $rules = $schema->createTable('Rules');
        $rules->addColumn('rule_id', 'guid');
        $rules->addColumn('id', 'string', ['length' => 64]);
        $rules->addColumn('type', 'string', ['length' => 64]);
        $rules->addColumn('published', 'datetime', ['notnull' => false]); // Nullable.
        $rules->addColumn('isSynthetic', 'boolean', ['default' => false]);
        $rules->setPrimaryKey(['rule_id']);

        $references = $schema->createTable('References');
        $references->addColumn('on_id', 'guid');
        $references->addColumn('subject_id', 'guid');
        $references->setPrimaryKey(['on_id', 'subject_id']);
        $references->addForeignKeyConstraint($rules, ['on_id'], ['rule_id'], ['onUpdate' => 'CASCADE']);
        $references->addForeignKeyConstraint($rules, ['subject_id'], ['rule_id'], ['onUpdate' => 'CASCADE']);
        $drops = [];
        if ($input->getOption('drop')) {
            $drops = $schema->toDropSql(new MySQL57Platform());
        }
        $arrayOfSqlQueries = array_merge($drops, $schema->toSql(new MySQL57Platform()));

        foreach ($arrayOfSqlQueries as $query) {
            try {
                $this->db->exec($query);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);

                return;
            }
        }
        $this->logger->info('Database created successfully.');
    }
}
