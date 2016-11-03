<?php

namespace MigrateComfortable\Wrapper;

use MigrateComfortable\Util;
use Symfony\Component\Console\Output\Output;

class MigrationOutputWrapper extends Output
{

    static $printData = true;

    public static function setPrint($status)
    {
        self::$printData = $status;
    }

    /**
     * Writes a message to the output.
     *
     * @param string $message A message to write to the output
     * @param Boolean $newline Whether to add a newline or not
     */
    protected function doWrite($message, $newline)
    {
        Util::printData($message, self::$printData);
    }
}