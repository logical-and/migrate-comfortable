<?php

/**
 * Migrate to latest version
 *
 * @author And <and.webdev@gmail.com>
 */

require_once __DIR__ . '/src/bootstrap.php';

MigrateComfortable\Migrator::construct()->migrateCommand();