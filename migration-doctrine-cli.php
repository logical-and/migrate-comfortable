<?php

require_once __DIR__ . '/src/bootstrap.php';

$migrator = MigrateComfortable\Migrator::construct();
$helperSet = $migrator->buildConsoleApplication()->getHelperSet();

Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet, array(
	// Migrations Commands
	$migrator->buildMigrationObject('DiffCommand'),
	$migrator->buildMigrationObject('ExecuteCommand'),
	$migrator->buildMigrationObject('GenerateCommand'),
	$migrator->buildMigrationObject('MigrateCommand'),
	$migrator->buildMigrationObject('StatusCommand'),
	$migrator->buildMigrationObject('VersionCommand')
));