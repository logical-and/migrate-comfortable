<?php

if (!function_exists('v'))
{
	function mc_get_composer_autoload_path()
	{
		foreach (array(
			'../../vendor/autoload.php',
			'../../../../../vendor/autoload.php',
			'../../../../../../vendor/autoload.php',
		) as $autoloadFile)
		{
			$autoloadFile = __DIR__ . '/' . $autoloadFile;
			if (is_file($autoloadFile)) return $autoloadFile;
		}

		throw new Exception('Unable to find composer autoload file');
	}
}

require_once mc_get_composer_autoload_path();

