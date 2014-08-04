<?php

namespace MigrateComfortable\EnvConfigurationLoader;

use MigrateComfortable\MigrationException;

class CodeIgniterLoader extends AbstractLoader {

	protected $defaultSettings = array(
		'basepath'       => 'basepath/directory',
		'section'        => 'admin',
		'database_group' => 'default'
	);

	public function loadConfiguration()
	{
		$configuration = array();
		$databaseGlobalVariable = 'db';


		if (!defined('BASEPATH')) define('BASEPATH', $this->settings['basepath']);
		require $this->settings['basepath'] . 'application/' . $this->settings['section'] . '/config/database.php';
		$loaded = ${$databaseGlobalVariable};
		$loaded = $loaded[ $this->settings[ 'database_group' ] ];

		$configuration += array(
			'databaseDriver'   => $this->fetchDriver($loaded[ 'dbdriver' ]),
			'databaseName'     => $loaded[ 'database' ],
			'databaseUser'     => $loaded[ 'username' ],
			'databasePassword' => $loaded[ 'password' ],
			'databaseHost'     => $loaded[ 'hostname' ]
		);

		return $configuration;
	}

	protected function validateSettings(array &$settings)
	{
		if (empty($settings[ 'basepath' ])) $this->confException('basepath', 'cannot be empty');
		if (!is_dir($settings[ 'basepath' ])) $this->confException('basepath', 'must be valid directory');

		return TRUE;
	}

	protected function completeSettings(array $settings)
	{
		$settings = parent::completeSettings($settings);
		$settings['basepath'] = realpath($this->directoryContext . $settings['basepath']) . '/';

		return $settings;
	}

	protected function fetchDriver($ciDriver)
	{
		$map = array(
			'mysql' => self::DRIVER_PDO_MYSQL
		);

		if (!empty($map[$ciDriver])) return $map[$ciDriver];

		throw new MigrationException("Unknown driver \"$ciDriver\"");
	}
}
 