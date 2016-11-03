<?php

namespace MigrateComfortable;

class Util
{
    public static function printData($text, $printResult = true)
    {
        if ($printResult) {
            if ('cli' == PHP_SAPI) {
                echo rtrim(strip_tags($text)) . "\n";
            } else {
                echo nl2br(rtrim($text) . "\n");
            }
        }
    }
}
