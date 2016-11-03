<?php

require_once __DIR__ . '/src/bootstrap.php';

MigrateComfortable\Migrator::construct()->storeDatabaseToMappingCommand();
echo 'Ok!';