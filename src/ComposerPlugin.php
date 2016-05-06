<?php

namespace MigrateComfortable;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;

        $refl = (new \ReflectionObject($composer->getConfig()))->getProperty('baseDir');
        $refl->setAccessible(true);
        $baseDir = $refl->getValue($composer->getConfig());
        @file_put_contents(__DIR__  . '/composer-json-path.txt', realpath($baseDir . '/composer.json'));
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'createMigrationsConfiguration',
            'post-update-cmd' => 'createMigrationsConfiguration'
        ];
    }

    public function createMigrationsConfiguration()
    {
        Configuration::create();
    }
}
