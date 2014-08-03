<?php

/**
 * @var \Symfony\Component\Console\Helper\HelperSet $helperSet
 */
$helperSet = require __DIR__ . '/src/bootstrap/bootstrap_cli_helperset.php';

// Migration helpers
$helperSet->set(new \Symfony\Component\Console\Helper\DialogHelper(), 'dialog');

Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet, array(
	// Migrations Commands
	new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
	new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
	new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
	new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
	new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
	new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
));