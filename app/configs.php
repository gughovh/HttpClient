<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 3:51
 */
return [
    'client' => [
        'timeout' => 1000,
        'connectTimeout' => 200,
        'protocolVersion' => 1.1,
        // todo implements cookie
        // todo implements auth
        'blocking' => true,
        'filename' => false,
        'redirectNumber' => 0,
        'redirects' => 10,
        'transport' => null,
        'verifyname' => true,
        'threshold' => 100
    ],
    'log' => [
        'dir' => __DIR__.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'log',
    ]
];