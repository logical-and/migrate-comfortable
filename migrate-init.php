<?php

require_once __DIR__ . '/src/migration_functions.php';

MigrateComfortable\storeDatabaseToMapping(require __DIR__ . '/src/bootstrap/bootstrap_orm.php', TRUE);
echo 'Ok!';