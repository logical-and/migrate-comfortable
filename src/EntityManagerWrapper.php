<?php

namespace MigrateComfortable;

use Composer\Autoload\ClassLoader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class EntityManagerWrapper {

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var Configuration
	 */
	protected $configuration;

	public function __construct()
	{

	}

	public function useConfiguration(Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	public function getConfiguration()
	{
		if ($this->configuration) return $this->configuration;
		else return $this->configuration = new Configuration();
	}

	public function getEntityManager()
	{
		if (!$this->entityManager)
		{
			$configuration = $this->getConfiguration();
			$classLoader = new ClassLoader();
			$classLoader->set('Migration\\', $configuration->getMigrationRootDir());
			$classLoader->register();

			$emConfig = Setup::createAnnotationMetadataConfiguration(
				array($configuration->getEntitiesDir()), $configuration->isDevMode(), null, null, false
			);
			$em     = EntityManager::create(array(
				'driver'   => $configuration->getDatabaseDriver(),
				'user'     => $configuration->getDatabaseUser(),
				'password' => $configuration->getDatabasePassword(),
				'dbname'   => $configuration->getDatabaseName(),
				'charset'  => !$configuration->getDatabaseCharset() ?: $configuration->getDatabaseCharset(),
			), $emConfig);

			$this->entityManager = $em;
		}

		return $this->entityManager;
	}
}
 