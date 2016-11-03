<?php

namespace MigrateComfortable;

use Doctrine\DBAL\Migrations\Configuration\Configuration as MigrationConfiguration;
use Doctrine\DBAL\Migrations\Configuration\JsonConfiguration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\ORM\Tools\Export\Driver\AnnotationExporter;
use Doctrine\ORM\Tools\SchemaTool;
use InvalidArgumentException;
use MigrateComfortable\EnvConfigurationLoader\ArrayInFileLoader;
use MigrateComfortable\EnvConfigurationLoader\CodeIgniterLoader;
use MigrateComfortable\Wrapper\DiffCommandWrapper;
use MigrateComfortable\Wrapper\EntityManagerWrapper;
use MigrateComfortable\Wrapper\MigrationOutputWrapper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class Migrator
{

    /**
     * @var EntityManagerWrapper
     */
    protected $emw;

    /**
     * @var MigrationConfiguration
     */
    protected $migrationsConfiguration;

    protected $printResult = true;

    public static function construct()
    {
        static $instance;

        if (!$instance) {
            $instance = new static();
        }

        return $instance;
    }

    public function setOutput($outputResult = true)
    {
        $this->printResult = (bool) $outputResult;

        return $this;
    }

    // --- Commands: aggregators

    public function generateMigrationCommand()
    {
        $this->goToRightDirectory();
        $version = $this->getMigrationsConfiguration()->getLatestVersion();
        $this->generateDiffCommand();

        // Generated?
        if ($this->getMigrationsConfiguration()->getLatestVersion() != $version) {
            $output = $this->printResult;
            $this->setOutput(false);

            $this->storeDatabaseToMappingCommand();
            $this->executeMigrationObject('VersionCommand', [
                '--add'            => true,
                'version'          => $this->getMigrationsConfiguration()->getLatestVersion(),
                '--no-interaction' => true
            ]);

            $this->setOutput($output);
        }
    }

    public function migrateCommand()
    {
        $this->goToRightDirectory();
        $this->migrateToCommand(null);
    }

    public function migrateToCommand($version)
    {
        $this->goToRightDirectory();
        $this->migrateToVersionCommand($version);
        $this->storeDatabaseToMappingCommand();
    }

    // --- Commands: executors

    public function storeDatabaseToMappingCommand()
    {
        $this->goToRightDirectory();
        $emw = $this->getEntityManagerWrapper();

        $entityNamespace = $emw->getConfiguration()->getEntitiesNS() . '\\';
        $destPath        = $emw->getConfiguration()->getMigrationRootDir();
        $mappingType     = 'annotation';

        $databaseDriver = new DatabaseDriver($emw->getEntityManager()->getConnection()->getSchemaManager());
        $databaseDriver->setNamespace($entityNamespace);
        $emw->getEntityManager()->getConfiguration()->setMetadataDriverImpl($databaseDriver);

        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($emw->getEntityManager());
        $metadata = $cmf->getAllMetadata();

        if (!file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Mapping destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } else {
            if (!is_writable($destPath)) {
                throw new \InvalidArgumentException(
                    sprintf("Mapping destination directory '<info>%s</info>' does not have write permissions.",
                        $destPath)
                );
            }
        }

        /**
         * @var AnnotationExporter $exporter
         */
        $exporter = $this->buildExporter($mappingType, $destPath);
        $exporter->setOverwriteExistingFiles(true);

        if ('annotation' == $mappingType) {
            $entityGenerator = new EntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);

            $entityGenerator->setNumSpaces(2);
        }

        if (count($metadata)) {
            foreach ($metadata as $class) {
                Util::printData(sprintf('Processing entity "<info>%s</info>"', $class->name), $this->printResult);
            }
            $exporter->setMetadata($metadata);
            $exporter->export();

            Util::printData(sprintf(PHP_EOL . 'Exporting "<info>%s</info>" mapping information to "<info>%s</info>"' . PHP_EOL,
                $mappingType, $destPath), $this->printResult);
        }
    }

    public function storeMappingToDatabaseCommand()
    {
        $this->goToRightDirectory();
        $metadatas = $this->getEntityManagerWrapper()->getEntityManager()->getMetadataFactory()->getAllMetadata();
        if (!empty($metadatas)) {
            // Create SchemaTool
            $schemaTool = new SchemaTool($this->getEntityManagerWrapper()->getEntityManager());

            // Defining if update is complete or not (--complete not defined means $saveMode = true)
            $complete = false;
            $dumpSql  = true;
            $force    = false;

            $sqls = $schemaTool->getUpdateSchemaSql($metadatas, $complete);
            if (0 == count($sqls)) {
                Util::printData('Nothing to update - your database is already in sync with the current entity metadata.',
                    $this->printResult);

                return;
            }

            if (($dumpSql && $force) OR (!$dumpSql && !$force)) {
                throw new InvalidArgumentException('You can pass either the --dump-sql or the --force option (but not both simultaneously).');
            }

            if ($dumpSql) {
                Util::printData(implode(';' . PHP_EOL, $sqls), true);
            } else {
                if ($force) {
                    Util::printData('Updating database schema...');
                    $schemaTool->updateSchema($metadatas, $complete);
                    Util::printData(sprintf('Database schema updated successfully! "<info>%s</info>" queries were executed',
                        count($sqls)));
                }
            }
        } else {
            Util::printData('No Metadata Classes to process.' . PHP_EOL, $this->printResult);
        }
    }

    public function generateDiffCommand()
    {
        $this->goToRightDirectory();
        $this->getEntityManagerWrapper()->getConfiguration()->getMigrationsDir(); // just ensure it exists
        $this->executeMigrationObject(get_class(new DiffCommandWrapper()), []);
    }

    public function migrateToVersionCommand($version = null)
    {
        $this->goToRightDirectory();
        $this->executeMigrationObject('MigrateCommand', [
            'version'          => $version,
            '--dry-run'        => false,
            '--no-interaction' => true
        ]);
        // storeDatabaseToMappingCommand($em, $printResult);
    }

    // --- API

    public function getEntityManagerWrapper()
    {
        if (!$this->emw) {
            require_once __DIR__ . '/bootstrap.php';

            $this->emw = new EntityManagerWrapper();
        }

        return $this->emw;
    }

    public function getMigrationsVersions()
    {
        $this->goToRightDirectory();
        $versions = [];

        // Add first version
        $versions[] = 0;

        foreach ($this->getMigrationsConfiguration()->getMigrations() as $migration) {
            /** @var Version $migration */
            $versions[] = $migration->getVersion();
        }

        return $versions;
    }

    public function getCurrentVersion()
    {
        $this->goToRightDirectory();

        return $this->getMigrationsConfiguration()->getCurrentVersion();
    }


    // --- Helpers

    protected function goToRightDirectory()
    {
        chdir($this->getEntityManagerWrapper()->getConfiguration()->getDirectoryContext());
    }

    protected function getMigrationsConfiguration()
    {
        if (!$this->migrationsConfiguration) {
            $this->migrationsConfiguration = new JsonConfiguration($this->getEntityManagerWrapper()->getEntityManager()->getConnection());

            // Create tmp file
            $tmpFile = tempnam(sys_get_temp_dir(), 'mc-');
            $config  = $this->getEntityManagerWrapper()->getConfiguration()->getRawConfig();
            file_put_contents($tmpFile, json_encode(
                    array_diff_key($config, array_flip([
                        // allowed keys
                        'environment_type',
                        CodeIgniterLoader::TYPE,
                        ArrayInFileLoader::TYPE
                    ])))
            );

            $this->migrationsConfiguration->load($tmpFile);

            // Remove tmp file
            unlink($tmpFile);
        }

        return $this->migrationsConfiguration;
    }

    protected function executeMigrationObject($objectName, array $options = [])
    {
        $object = $this->buildMigrationObject($objectName);

        // Fix
        $object->addOption('no-interaction', null, InputOption::VALUE_OPTIONAL,
            'Execute the migration without a warning message which you need to interact with', false);
        $input = $this->buildInput($options, $object->getDefinition());
        if (!empty($options[ '--no-interaction' ]) or !empty($options[ '-n' ])) {
            $input->setInteractive(false);
        }

        // Use call_user_func, such as IDE look in Command class, and execute method has protected access
        call_user_func([$object, 'execute'], $input, $this->buildOutput());
    }

    // --- Builders

    public function buildMigrationObject($objectName)
    {
        // Complete the name
        if (false === strpos($objectName, '\\')) {
            $objectName = 'Doctrine\DBAL\Migrations\Tools\Console\Command\\' . $objectName;
        }

        /** @var Command|AbstractCommand $object */
        $object = new $objectName();
        $object->setApplication($this->buildConsoleApplication());
        $object->setMigrationConfiguration($this->getMigrationsConfiguration());

        return $object;
    }

    public function buildConsoleApplication()
    {
        $application = new Application();
        $application->setHelperSet(new HelperSet([
            'db' => new ConnectionHelper($this->getEntityManagerWrapper()->getEntityManager()->getConnection()),
            'em' => new EntityManagerHelper($this->getEntityManagerWrapper()->getEntityManager())
        ]));

        if (class_exists('\Symfony\Component\Console\Helper\QuestionHelper')) {
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $application->getHelperSet()->set(new \Symfony\Component\Console\Helper\QuestionHelper(), 'question');
        } else {
            /** @noinspection PhpParamsInspection */
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $application->getHelperSet()->set(new \Symfony\Component\Console\Helper\DialogHelper(), 'dialog');
        }

        return $application;
    }

    public function buildInput(array $options = [], InputDefinition $definition)
    {
        return new ArrayInput($options, $definition);
    }

    public function buildOutput()
    {
        MigrationOutputWrapper::setPrint($this->printResult);

        return new MigrationOutputWrapper();
    }

    public function buildExporter($toType, $destPath)
    {
        $cme = new ClassMetadataExporter();

        return $cme->getExporter($toType, $destPath);
    }
}
