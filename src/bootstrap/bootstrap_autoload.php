<?php

if (!function_exists('mc_get_composer_autoload_path'))
{
	function mc_get_composer_autoload_path()
	{
		foreach (array(
			'../../vendor/composer/installed.json',
			'../../../../composer/installed.json',
			'../../../../../composer/installed.json',
		) as $installedList)
		{
			$installedList = __DIR__ . '/' . $installedList;
			if (is_file($installedList)) {
				return realpath(dirname(dirname($installedList)) . '/autoload.php');
			}
		}

		throw new Exception('Unable to find composer autoload file');
	}
}

require_once mc_get_composer_autoload_path();