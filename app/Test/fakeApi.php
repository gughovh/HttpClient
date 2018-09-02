<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 14:58
 */

$ds = DIRECTORY_SEPARATOR;

require __DIR__.$ds.'..'.$ds.'..'.$ds.'vendor'.$ds.'autoload.php';

$fakeApi = new \App\Api\FakeApiClient;

var_dump(
    $fakeApi->getTestObject(6),
    $fakeApi->createTestObject(new \App\Test\TestObject([
        'name' => 'Test',
        'last_name' => 'Petrov',
        'address' => [
            'id' => 1,
            'country' => 'Russia',
            'iso_code' => 'ru',
            'city' => 'Moscow',
        ]
    ])),
    $fakeApi->updateTestObject(8, new \App\Test\TestObject([
        'name' => 'Test updated',
        'last_name' => 'Ivanov',
        'address' => [
            'id' => 1,
            'country' => 'Russia',
            'iso_code' => 'ru',
            'city' => 'Moscow',
        ]
    ])),
    $fakeApi->deleteTestObject(68)
);