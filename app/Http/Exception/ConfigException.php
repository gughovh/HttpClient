<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 31.08.2018
 * Time: 0:26
 */

namespace App\Http\Exception;


class ConfigException extends HttpException
{
    /**
     * @var string
     */
    protected $message = 'App\Http client configuration error.';
}