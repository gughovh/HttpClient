<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 14:56
 */

// todo Serve this to test fake api on localhost:81
// todo cd app/Test/api
// todo php -S localhost:81
// todo php app/Test/fakeApi.php

if ($_SERVER['REQUEST_METHOD'] == 'GET' && strpos($_SERVER['REQUEST_URI'], '/get/test_object') === 0) {
    die(json_encode([
        'id' => $_GET['id'],
        'name' => 'Test',
        'last_name' => 'Petrov',
        'address' => [
            'id' => 1,
            'country' => 'Russia',
            'iso_code' => 'ru',
            'city' => 'Moscow',
        ]
    ]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && strpos($_SERVER['REQUEST_URI'], '/create/test_object') === 0) {
    $data = $_POST;
    $id = $data['id'] ?? 0;

    die(json_encode(array_merge([
        'id' => ++$id,
    ], $data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT' && strpos($_SERVER['REQUEST_URI'], '/update/test_object') === 0) {
    die(json_encode([
        'success' => true,
        'testObject' => array_merge([
            'name' => 'Test',
            'last_name' => 'Petrov',
            'address' => [
                'id' => 1,
                'country' => 'Russia',
                'iso_code' => 'ru',
                'city' => 'Moscow',
            ]
        ], $_POST, ['id' => $_GET['id']])
    ]));
}

if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && strpos($_SERVER['REQUEST_URI'], '/delete/test_object') === 0) {
    die(json_encode(['success' => true]));
}