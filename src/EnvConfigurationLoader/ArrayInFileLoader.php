<?php

namespace MigrateComfortable\EnvConfigurationLoader;

class ArrayInFileLoader extends AbstractLoader
{
    const TYPE = 'array_in_file';

    protected $defaultSettings = [
        'file'     => '',
        'variable' => '',
        'mapping'  => [
            'driver'   => 'databaseDriver',
            'database' => 'databaseName',
            'user'     => 'databaseUser',
            'password' => 'databasePassword',
            'host'     => 'databaseHost',
            'charset'  => 'databaseCharset'
        ],
        'default'  => [
            'databaseDriver'  => 'mysql',
            'databaseHost'    => 'localhost',
            'databaseCharset' => 'utf8'
        ]
    ];

    protected function validateSettings(array $settings)
    {
        if (empty($settings[ 'file' ])) {
            $this->confException('file', 'cannot be empty!');
        } elseif (!is_file($this->directoryContext . $settings[ 'file' ] . '.php')) {
            $this->confException('file',
                sprintf('"%" is not exists!', $this->directoryContext . $settings[ 'file' ] . '.php'));
        }

        if (empty($settings[ 'mapping' ])) {
            $this->confException('mapping', 'cannot be empty!');
        }

        return true;
    }

    protected function completeSettings(array $settings)
    {
        $settings           = parent::completeSettings($settings);
        $settings[ 'file' ] = realpath($this->directoryContext . $settings[ 'file' ] . '.php');

        return $settings;
    }

    public function loadConfiguration()
    {
        $configuration = [];
        $_variableKey  = $this->settings[ 'variable' ];

        /** @noinspection PhpIncludeInspection */
        $loadedConfiguration = include $this->settings[ 'file' ];

        if ($_variableKey) {
            if (!isset($$_variableKey)) {
                $this->confException('variable', 'no variable is found in file!');
            }

            $loadedConfiguration = $$_variableKey;
        }
        elseif (is_array($loadedConfiguration)) {
            ; // ok
        }
        else {
            $this->confException('variable', 'is not defined or file returns no data');
        }

        if (!is_array($loadedConfiguration)) {
            $this->confException('variable', 'loaded variable is not array');
        }

        // Get values from configuration
        foreach ($this->settings[ 'mapping' ] as $from => $to) {
            if (isset($loadedConfiguration[ $from ])) {
                $configuration[ $to ] = $loadedConfiguration[ $from ];
            }
        }

        // Use default values if needed
        foreach ($this->settings[ 'default' ] as $key => $value) {
            if (!isset($configuration[ $key ])) {
                $configuration = $value;
            }
        }

        return $configuration;
    }
}
