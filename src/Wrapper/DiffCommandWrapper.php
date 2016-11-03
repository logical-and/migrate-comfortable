<?php

namespace MigrateComfortable\Wrapper;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Symfony\Component\Console\Input\InputInterface;

class DiffCommandWrapper extends DiffCommand {

    protected function generateMigration(Configuration $configuration, InputInterface $input, $version, $up = NULL,
        $down = NULL)
    {
        return parent::generateMigration($configuration, $input, $version,
            /* switch them, as we follow the database changes, not mapping */
            $down, $up);
    }
}