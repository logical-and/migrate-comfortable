<?php

require_once __DIR__ . '/src/bootstrap/bootstrap_autoload.php';

\MigrateComfortable\Configuration::create()->getDatabaseHost();
echo 'Ok!';