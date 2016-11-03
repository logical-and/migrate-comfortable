<?php

/**
 * Update or store migration to files
 *
 * @author And <and.webdev@gmail.com>
 */

require_once __DIR__ . '/src/bootstrap.php';

MigrateComfortable\Migrator::construct()->generateMigrationCommand();