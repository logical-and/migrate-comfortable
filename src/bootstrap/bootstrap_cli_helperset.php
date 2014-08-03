<?php

/** @var \MigrateComfortable\EntityManagerWrapper $emWrapper */
$emWrapper = require __DIR__ . '/bootstrap_orm.php';
$em = $emWrapper->getEntityManager();
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));
return $helperSet;