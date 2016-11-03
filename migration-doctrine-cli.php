<?php

/**
 * @var \Symfony\Component\Console\Helper\HelperSet $helperSet
 */
$emw = require __DIR__ . '/src/bootstrap/bootstrap_orm.php';
require_once __DIR__ . '/src/migration_functions.php';

/**
 * @var \Symfony\Component\Console\Helper\QuestionHelper $app
 */
$app = \MigrateComfortable\_getApplication($emw);
/**
 * @var \Symfony\Component\Console\Helper\HelperSet $helperSet
 */
$helperSet = $app->getHelperSet();

Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet, array(
	// Migrations Commands
	\MigrateComfortable\_getMigrationObject('DiffCommand', $emw),
	\MigrateComfortable\_getMigrationObject('ExecuteCommand', $emw),
	\MigrateComfortable\_getMigrationObject('GenerateCommand', $emw),
	\MigrateComfortable\_getMigrationObject('MigrateCommand', $emw),
	\MigrateComfortable\_getMigrationObject('StatusCommand', $emw),
	\MigrateComfortable\_getMigrationObject('VersionCommand', $emw)
));