<?php

/**
 * Migrate to selected version
 *
 * @author And <and.webdev@gmail.com>
 */

require_once __DIR__ . '/src/bootstrap.php';

$migrator = new MigrateComfortable\Migrator();

$migrated = false;

if (isset($_POST[ 'version' ]))
{
	$migrator->migrateToCommand($_POST[ 'version' ]);
	$migrated = true;
};

$currentVersion = $migrator->getCurrentVersion();
?>
<? if ($migrated): ?>
	<br><br>
<? endif ?>
<form action="" method="POST">
	<select name="version">
		<? foreach (array_reverse($migrator->getMigrationsVersions()) as $version): ?>
			<? if ($version == $currentVersion): ?>
				<option value="<?= $version ?>" selected="selected"><?= $version ?></option>
			<? else: ?>
				<option value="<?= $version ?>"><?= $version ?></option>
			<? endif ?>
		<? endforeach ?>
	</select>
	<button>Migrate to selected version</button>
</form>