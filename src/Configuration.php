<?php

namespace MigrateComfortable;

use MigrateComfortable\EnvConfigurationLoader\AbstractLoader;
use MigrateComfortable\EnvConfigurationLoader\ArrayInFileLoader;
use MigrateComfortable\EnvConfigurationLoader\CodeIgniterLoader;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/bootstrap/bootstrap_autoload.php';

class Configuration {

	/** @var  AbstractLoader */
	protected $loader;
	protected $yamlConfig = array();
	protected $directoryContext = '';

	public static function create()
	{
		return new self();
	}

	public function __construct()
	{
		// Plugin way
		if (is_file(__DIR__ . '/composer-json-path.txt') and
			($data = file_get_contents(__DIR__ . '/composer-json-path.txt'))) {

			$this->directoryContext = dirname($data) . '/';
		}
		// Common way
		else {
			$this->directoryContext = realpath(dirname(dirname(mc_get_composer_autoload_path()))) . '/';
		}

		// Go outside the vendor's dir
		$config = $this->directoryContext . 'migrations.yml';
		if (!file_exists($config)) copy(__DIR__ . '/../migrations.yml', $config);

		$this->yamlConfig = Yaml::parse($config, TRUE);
	}

	public function useLoader($type, array $args = array())
	{
		switch ($type)
		{
			case CodeIgniterLoader::TYPE:
				$this->loader = new CodeIgniterLoader($args, $this->directoryContext);
				break;

			case ArrayInFileLoader::TYPE:
				$this->loader = new ArrayInFileLoader($args, $this->directoryContext);
				break;

			default:
				throw new MigrationException("Loader with type \"$type\" is unknown!");
		}

		return $this;
	}

	public function determineLoader()
	{
		$type = $this->yamlConfig['environment_type'];
		if (!$type) throw new MigrationException('Environment type isn\'t set!');
		$args = !empty($this->yamlConfig[$type]) ? $this->yamlConfig[$type] : array();
		$args = array_filter($args, function($value) { return !is_null($value); });

		$this->useLoader($type, $args);

		return $this;
	}

	// --- Accessors

	public function getDatabaseDriver()
	{
		return $this->getEnvConfigLoader()->getConfigurationValue('databaseDriver');
	}

	public function getDatabaseUser()
	{
		return $this->getEnvConfigLoader()->getConfigurationValue('databaseUser');
	}

	public function getDatabasePassword()
	{
		return $this->getEnvConfigLoader()->getConfigurationValue('databasePassword');
	}

	public function getDatabaseName()
	{
		return $this->getEnvConfigLoader()->getConfigurationValue('databaseName');
	}

	public function getDatabaseHost()
	{
		return $this->getEnvConfigLoader()->getConfigurationValue('databaseHost');
	}

	public function getDatabaseCharset()
	{
		return $this->getEnvConfigLoader()->getConfigurationValue('databaseCharset');
	}

	public function getEntitiesNS()
	{
		return dirname($this->yamlConfig['migrations_namespace']) . '\\Mapping';
	}

	public function getEntitiesDir()
	{
		return $this->ensureDirExists($this->getMigrationRootDir() . str_replace('\\', '/', $this->getEntitiesNS()));
	}

	public function getMigrationsDir()
	{
		return $this->ensureDirExists($this->getMigrationRootDir() . str_replace('\\', '/', $this->yamlConfig['migrations_namespace']) . '/');
	}

	public function getMigrationRootDir()
	{
		return $this->ensureDirExists(
			$this->directoryContext .
			(false !== strpos($this->yamlConfig['migrations_directory'], '/') ?
				substr($this->yamlConfig['migrations_directory'], 0, strpos($this->yamlConfig['migrations_directory'], '/')) :
				$this->yamlConfig['migrations_directory']
			) .
			'/'
		);
	}

	public function isDevMode()
	{
		return TRUE;
	}

	public function getDirectoryContext()
	{
		return $this->directoryContext;
	}

	// --- Internal methods

	protected function getEnvConfigLoader()
	{
		if (!$this->loader)
		{
			$this->determineLoader();
		}

		return $this->loader;
	}

	protected function ensureDirExists($dir)
	{
		if (!is_dir($dir)) mkdir($dir, 0777, TRUE);

		return $dir;
	}
}