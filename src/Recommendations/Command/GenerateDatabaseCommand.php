<?php

namespace eLife\Recommendations\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use eLife\Logging\Monitoring;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GenerateDatabaseCommand extends Command
{
    private $db;
    private $logger;
    private $monitoring;
    private $schema;
    private $rulesTableName;
    private $referencesTableName;

    public function __construct(
        Connection $db,
        LoggerInterface $logger,
        Monitoring $monitoring,
        string $rulesTableName,
        string $referencesTableName
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->monitoring = $monitoring;
        $this->schema = $db->getSchemaManager();
        $this->rulesTableName = $rulesTableName;
        $this->referencesTableName = $referencesTableName;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('generate:database')
            ->setDescription('Creates schema for recommendations database.')
            ->addOption('drop', 'd', InputOption::VALUE_NONE);
    }

    private function tableExists(string $table): bool
    {
        return in_array($table, $this->schema->listTableNames());
    }

    private function createTables(bool $drop, Table ...$tables)
    {
        foreach (array_reverse($tables) as $table) {
            if ($drop) {
                $this->db->getSchemaManager()->dropTable($table->getName());
            }
        }
        foreach ($tables as $table) {
            $this->db->getSchemaManager()->createTable($table);
        }
    }

    private function allTablesExist(string ...$tables)
    {
        $r = true;
        foreach ($tables as $table) {
            $r = $this->tableExists($table) && $r;
        }

        return $r;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $drop = $input->getOption('drop');
        $this->monitoring->nameTransaction('Scheme creation');
        $this->monitoring->startTransaction();
        $schema = new Schema();

        if (
            $drop === false &&
            $this->allTablesExist($this->rulesTableName, $this->referencesTableName)
        ) {
            $this->logger->info('Database already exists, skipping.');

            return;
        }

        $rules = $schema->createTable($this->rulesTableName);
        $rules->addColumn('rule_id', 'guid');
        $rules->addColumn('id', 'string', ['length' => 64]);
        $rules->addColumn('type', 'string', ['length' => 64]);
        $rules->addColumn('published', 'datetime', ['notnull' => false]); // Nullable.
        $rules->addColumn('isSynthetic', 'boolean', ['default' => false]);
        $rules->setPrimaryKey(['rule_id']);

        $references = $schema->createTable($this->referencesTableName);
        $references->addColumn('on_id', 'guid');
        $references->addColumn('subject_id', 'guid');
        $references->setPrimaryKey(['on_id', 'subject_id']);
        $references->addForeignKeyConstraint($rules, ['on_id'], ['rule_id'], ['onUpdate' => 'CASCADE']);
        $references->addForeignKeyConstraint($rules, ['subject_id'], ['rule_id'], ['onUpdate' => 'CASCADE']);

        try {
            if ($drop) {
                // Disable foreign key checks
                $this->db->query(sprintf('SET FOREIGN_KEY_CHECKS=%s', (int) false));
            }
            // Only need to create references since its cascades.
            $this->createTables($drop, $rules, $references);
            if ($drop) {
                // Re-enable foreign key checks.
                $this->db->query(sprintf('SET FOREIGN_KEY_CHECKS=%s', (int) true));
            }
        } catch (Throwable $e) {
            $this->monitoring->recordException($e, 'Problem creating database schema.');
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            throw $e;
        } finally {
            $this->monitoring->endTransaction();
        }
        $this->logger->info('Database created successfully.', [
            'tables' => [$this->referencesTableName, $this->rulesTableName],
        ]);
    }
}
