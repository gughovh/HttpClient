<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 3:52
 */

namespace App\Http\Helper;

/**
 * @return array
 */
function configs() :array
{
    static $configs;

    if (is_null($configs)) {
        $ds = DIRECTORY_SEPARATOR;
        $configs = require __DIR__.$ds.'..'.$ds.'..'.$ds.'configs.php';
    }

    return $configs;
}
