<?php
use MigrateComfortable\EntityManagerWrapper;

/**
 * Migrate to selected version
 *
 * @author And <and.webdev@gmail.com>
 */

/**
 * @var EntityManagerWrapper $emw
 */
$emw = require __DIR__ . '/src/bootstrap/bootstrap_orm.php';
require __DIR__ . '/src/migration_functions.php';

$migrated = FALSE;

if (isset($_POST[ 'version' ]))
{
	MigrateComfortable\migrateTo($_POST[ 'version' ], $emw, TRUE);
	$migrated = TRUE;
};

$currentVersion = MigrateComfortable\getCurrentVersion($emw);
?>
<? if ($migrated): ?>
	<br><br>
<? endif ?>
<form action="" method="POST">
	<select name="version">
		<? foreach (array_reverse(MigrateComfortable\getMigrationsVersions($emw)) as $version): ?>
			<? if ($version == $currentVersion): ?>
				<option value="<?= $version ?>" selected="selected"><?= $version ?></option>
			<? else: ?>
				<option value="<?= $version ?>"><?= $version ?></option>
			<? endif ?>
		<? endforeach ?>
	</select>
	<button>Мигрировать на выбранную версию</button>
</form>