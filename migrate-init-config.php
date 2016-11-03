<?php

require_once __DIR__ . '/src/bootstrap.php';

\MigrateComfortable\Configuration::create()->getDatabaseHost();
echo 'Ok!';