<?php

/**
 * Migrate to latest version
 *
 * @author And <and.webdev@gmail.com>
 */

require_once __DIR__ . '/src/migration_functions.php';
MigrateComfortable\migrate(require __DIR__ . '/src/bootstrap/bootstrap_orm.php', TRUE);