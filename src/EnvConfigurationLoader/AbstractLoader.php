<?php

namespace MigrateComfortable\EnvConfigurationLoader;

use MigrateComfortable\MigrationException;

abstract class AbstractLoader {

	const DRIVER_PDO_MYSQL = 'pdo_mysql';

	const LOADER_CODEIGNITER = 'codeigniter';

	protected $defaultSettings = array();
	protected $settings = array();
	protected $configuration = array();

	public function __construct(array $settings)
	{
		if (!$this->validateSettings($settings))
		{
			throw new MigrationException('Wrong settings!');
		}

		$this->settings = $this->completeSettings($settings);
	}

	abstract public function loadConfiguration();

	public function getConfiguration()
	{
		if (!$this->configuration)
		{
			$this->configuration = $this->loadConfiguration();
		}

		return $this->configuration;
	}

	public function getConfigurationValue($key)
	{
		$configuration = $this->getConfiguration();
		if (!isset($configuration[ $key ])) throw new MigrationException("\"$key\" is unknown configuration key!");

		return $configuration[ $key ];
	}

	// --- Internal methods

	protected function validateSettings(array $settings)
	{
		return TRUE;
	}

	protected function completeSettings(array $settings)
	{
		return array_merge($this->defaultSettings, $settings);
	}

	protected function confException($variable, $message)
	{
		throw new MigrationException("Configuration error: \"$variable\" $message");
	}
}
 