<?php

/**
 * Update or store migration to files
 *
 * @author And <and.webdev@gmail.com>
 */

require_once __DIR__ . '/src/migration_functions.php';
MigrateComfortable\generateMigration(require __DIR__ . '/src/bootstrap/bootstrap_orm.php', TRUE);