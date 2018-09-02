<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 01.09.2018
 * Time: 5:11
 */

namespace App\Http\Helper;


class Log
{
    const ERROR = 'error';
    const DEBUG = 'debug';
    const INFO = 'info';

    const OPTIONS = [
        self::ERROR => 'errors.log',
        self::DEBUG => 'debug.log',
        self::INFO  => 'info.log',
        self::INFO  => 'info.log',
    ];

    public static function error($error)
    {
        if ($error instanceof \Throwable) {
            $error = "{$error->getCode()}\t{$error->getMessage()}\t\t{$error->getTraceAsString()}";
        }
        self::write(self::ERROR, $error);
    }

    public static function debug($debug)
    {
        self::write(self::DEBUG, $debug);
    }

    public static function info($info)
    {
        self::write(self::INFO, $info);
    }

    protected static function write($level, $txt)
    {
        $dir = static::getDir();
        $fileName = $dir . DIRECTORY_SEPARATOR . self::OPTIONS[$level];
        $date = date("Y-m-d H:i:s");
        $txt = "[{$date}] {$txt} \r\n";

        $handle = fopen($fileName,"a+");
        chmod($fileName, 0666);

        if ($handle === false) {
            return;
        }

        fwrite($handle,$txt);
        fclose($handle);
    }

    protected static function getDir()
    {
        return configs()['log']['dir'];
    }
}