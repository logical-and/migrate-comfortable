<?php

/**
 * Migration API and functions
 *
 * @author And <and.webdev@gmail.com>
 */

namespace MigrateComfortable;

require_once __DIR__ . '/bootstrap/bootstrap_autoload.php';

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\DBAL\Version as DbalVersion;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\ORM\Tools\Export\Driver\AnnotationExporter;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;

// Old-style code, yeah. But I wrote it for old php 5.2 project, and just added a namespace for now

// Aggregate functions

function generateMigration(EntityManagerWrapper $emw, $printResult = TRUE)
{
	goToRightDirectory($emw);
	$version = _getMigrationsConfiguration($emw)->getLatestVersion();
	generateDiff($emw, $printResult);

	// Generated?
	if (_getMigrationsConfiguration($emw)->getLatestVersion() != $version)
	{
		storeDatabaseToMapping($emw, FALSE);
		_executeMigrationObject('VersionCommand', $emw, array(
			'--add'   => TRUE,
			'version' => _getMigrationsConfiguration($emw)->getLatestVersion()
		), FALSE);
	}
}

function migrate(EntityManagerWrapper $emw, $printResult = TRUE)
{
	goToRightDirectory($emw);
	migrateTo(NULL, $emw, $printResult);
}

function migrateTo($version, EntityManagerWrapper $emw, $printResult = TRUE)
{
	goToRightDirectory($emw);
	migrateToVersion($version, $emw, $printResult);
	storeDatabaseToMapping($emw, FALSE);
}

// API

function storeDatabaseToMapping(EntityManagerWrapper $emw, $printResult = FALSE)
{
	goToRightDirectory($emw);
	$entityNamespace = $emw->getConfiguration()->getEntitiesNS() . '\\';
	$destPath        = $emw->getConfiguration()->getMigrationRootDir();
	$mappingType     = 'annotation';

	$databaseDriver = new DatabaseDriver(
		$emw->getEntityManager()->getConnection()->getSchemaManager()
	);

	$emw->getEntityManager()->getConfiguration()->setMetadataDriverImpl(
		$databaseDriver
	);

	$databaseDriver->setNamespace($entityNamespace);

	$cmf = new DisconnectedClassMetadataFactory();
	$cmf->setEntityManager($emw->getEntityManager());
	$metadata = $cmf->getAllMetadata();

	if (! file_exists($destPath))
	{
		throw new \InvalidArgumentException(
			sprintf("Mapping destination directory '<info>%s</info>' does not exist.", $destPath)
		);
	} else if (! is_writable($destPath))
	{
		throw new \InvalidArgumentException(
			sprintf("Mapping destination directory '<info>%s</info>' does not have write permissions.", $destPath)
		);
	}

	/**
	 * @var AnnotationExporter $exporter
	 */
	$exporter = _getExporter($mappingType, $destPath);
	$exporter->setOverwriteExistingFiles(TRUE);

	if ($mappingType == 'annotation')
	{
		$entityGenerator = new EntityGenerator();
		$exporter->setEntityGenerator($entityGenerator);

		$entityGenerator->setNumSpaces(2);
	}

	if (count($metadata))
	{
		foreach ($metadata as $class)
		{
			printData(sprintf('Processing entity "<info>%s</info>"', $class->name), $printResult);
		}
		$exporter->setMetadata($metadata);
		$exporter->export();

		printData(sprintf(PHP_EOL . 'Exporting "<info>%s</info>" mapping information to "<info>%s</info>"' . PHP_EOL, $mappingType, $destPath), $printResult);
	}
}

function storeMappingToDatabase(EntityManagerWrapper $emw, $printResult = FALSE)
{
	goToRightDirectory($emw);
	$metadatas = $emw->getEntityManager()->getMetadataFactory()->getAllMetadata();
	if (! empty($metadatas))
	{
		// Create SchemaTool
		$schemaTool = new SchemaTool($emw->getEntityManager());

		// Defining if update is complete or not (--complete not defined means $saveMode = true)
		$complete = FALSE;
		$dumpSql  = TRUE;
		$force    = FALSE;

		$sqls = $schemaTool->getUpdateSchemaSql($metadatas, $complete);
		if (0 == count($sqls))
		{
			printData('Nothing to update - your database is already in sync with the current entity metadata.', $printResult);

			return;
		}

		if (($dumpSql && $force) OR (! $dumpSql && ! $force))
		{
			throw new InvalidArgumentException('You can pass either the --dump-sql or the --force option (but not both simultaneously).');
		}

		if ($dumpSql)
		{
			printData(implode(';' . PHP_EOL, $sqls), TRUE);
		} else if ($force)
		{
			printData('Updating database schema...');
			$schemaTool->updateSchema($metadatas, $complete);
			printData(sprintf('Database schema updated successfully! "<info>%s</info>" queries were executed', count($sqls)));
		}
	} else
	{
		printData('No Metadata Classes to process.' . PHP_EOL, $printResult);
	}
}

function generateDiff(EntityManagerWrapper $emw, $printResult = FALSE)
{
	goToRightDirectory($emw);
	$emw->getConfiguration()->getMigrationsDir(); // just ensure it exists
	_executeMigrationObject(__NAMESPACE__ . '\\MigrationDiff', $emw, array(), $printResult);
}

function migrateToVersion($version = NULL, EntityManagerWrapper $emw, $printResult = FALSE)
{
	goToRightDirectory($emw);
	_executeMigrationObject('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand', $emw, array(
		'version'          => $version,
		'--dry-run'        => FALSE,
		'--no-interaction' => TRUE
	), $printResult);
	// storeDatabaseToMapping($em, $printResult);
}

function getMigrationsVersions(EntityManagerWrapper $emw)
{
	goToRightDirectory($emw);
	$versions = array();

	// Add first version
	$versions[ ] = 0;

	foreach (_getMigrationsConfiguration($emw)->getMigrations() as $migration)
	{
		/** @var Version $migration */
		$versions[ ] = $migration->getVersion();
	}

	return $versions;
}

function getCurrentVersion(EntityManagerWrapper $emw)
{
	return _getMigrationsConfiguration($emw)->getCurrentVersion();
}

// Internals

function _getExporter($toType, $destPath)
{
	$cme = new ClassMetadataExporter();

	return $cme->getExporter($toType, $destPath);
}

function printData($text, $printResult = TRUE)
{
	if ($printResult) {
		if ('cli' == PHP_SAPI) echo rtrim(strip_tags($text)) . "\n";
		else echo nl2br(rtrim($text) . "\n");
	}
}

function _getApplication(EntityManagerWrapper $emw)
{
	$application = new Application();
	$application->setHelperSet(new HelperSet(array(
		'db'     => new ConnectionHelper($emw->getEntityManager()->getConnection()),
		'em'     => new EntityManagerHelper($emw->getEntityManager()),
		'dialog' => new DialogHelper()
	)));

	return $application;
}

function _getInput(array $options = array(), InputDefinition $definition)
{
	return new ArrayInput($options, $definition);
}

function _getOutput($printResult = TRUE)
{
	MigrationOutput::setPrint($printResult);

	return new MigrationOutput();
}

function _getMigrationObject($objectName, EntityManagerWrapper $emw)
{
	// Complete the name
	if (FALSE === strpos($objectName, '\\')) $objectName = 'Doctrine\DBAL\Migrations\Tools\Console\Command\\' . $objectName;

	/** @var Command $object */
	$object = new $objectName($objectName);
	$object->setApplication(_getApplication($emw));

	return $object;
}

function _executeMigrationObject($objectName, EntityManagerWrapper $emw, array $options = array(), $printResult = TRUE)
{
	$object = _getMigrationObject($objectName, $emw);

	// Fix
	$object->addOption('no-interaction', NULL, InputOption::VALUE_OPTIONAL, 'Execute the migration without a warning message which you need to interact with', FALSE);
	$input = _getInput($options, $object->getDefinition());
	if (!empty($options['--no-interaction']) OR !empty($options['-n'])) $input->setInteractive(FALSE);

	// Use call_user_func, such as IDE look in Command class, and execute method has protected access
	call_user_func(array($object, 'execute'), $input, _getOutput($printResult));
}

function _getMigrationsConfiguration(EntityManagerWrapper $emw)
{
	$object = _getMigrationObject('StatusCommand', $emw);
	$refl   = new \ReflectionObject($object);
	$method = $refl->getMethod('getMigrationConfiguration');
	$method->setAccessible(TRUE);
	/** @var Configuration $conf */
	$conf = $method->invoke($object, _getInput(array(), $object->getDefinition()), _getOutput(FALSE));
//	$conf = $object->getMigrationConfiguration(_getInput(array(), $object->getDefinition()), _getOutput(FALSE));
	$method->setAccessible(FALSE);

	return $conf;
}

/**
 * Such as migrations need to be in migrations directory, we need to change to dir
 *
 * @param EntityManagerWrapper $emw
 */
function goToRightDirectory(EntityManagerWrapper $emw)
{
	chdir($emw->getConfiguration()->getDirectoryContext());
}

class MigrationOutput extends Output {

	static $printData = TRUE;

	public static function setPrint($status)
	{
		self::$printData = $status;
	}

	/**
	 * Writes a message to the output.
	 *
	 * @param string  $message A message to write to the output
	 * @param Boolean $newline Whether to add a newline or not
	 */
	protected function doWrite($message, $newline)
	{
		printData($message, self::$printData);
	}
}


class MigrationDiff extends DiffCommand {

	protected function generateMigration(Configuration $configuration, InputInterface $input, $version, $up = NULL,
		$down = NULL)
	{
		return parent::generateMigration($configuration, $input, $version,
			/* switch them, as we follow the database changes, not mapping */
			$down, $up);
	}
}