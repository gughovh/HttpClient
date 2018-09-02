<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 14:01
 */

namespace App\Api;


use App\Test\TestObject;

class FakeApiClient extends AbstractApiClient
{
    const ADDRESS = 'http://localhost';
    const PORT = 81;

    /**
     * @return array
     */
    protected function getClientConfig(): array
    {
        return [
            'address' => self::ADDRESS,
            'port' => self::PORT,
        ];
    }

    /**
     * @param int $id
     * @return TestObject|null
     */
    public function getTestObject(int $id):? TestObject
    {
        $response = $this->getClient()->get('get/test_object', null, [
            'query' => [
                'id' => $id
            ]
        ]);

        if ($response->hasError()) {
            // todo handle error
            return null;
        }

        if (!is_array($data = json_decode($response->getBody(), true))) {
            // todo handle error
            return null;
        }

        return new TestObject($data);
    }

    /**
     * @param TestObject $testObject
     * @return null
     */
    public function createTestObject(TestObject $testObject)
    {
        $response = $this->getClient()->post('create/test_object', $testObject->getData());

        if ($response->hasError()) {
            // todo handle error
            return null;
        }

        if (!is_array($data = json_decode($response->getBody(), true))) {
            // todo handle error
            return null;
        }

        if (empty($data['id'])) {
            return null;
        }

        return $data['id'];
    }

    /**
     * @param int $id
     * @param TestObject $testObject
     * @return TestObject|null
     */
    public function updateTestObject(int $id, TestObject $testObject) :? TestObject
    {
        $response = $this->getClient()->update(
            'update/test_object',
            $testObject->getData(),
            null,
            ['query' => ['id' => $id]]
        );

        if ($response->hasError()) {
            // todo handle error
            return null;
        }

        if (!is_array($data = json_decode($response->getBody(), true))) {
            // todo handle error
            return null;
        }

        if (empty($data['success'])) {
            // todo handle error
            return null;
        }

        return new TestObject($data['testObject']);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteTestObject(int $id)
    {
        $response = $this->getClient()->delete('delete/test_object', null, ['query' => ['id' => $id]]);

        if ($response->hasError()) {
            // todo handle error
            return false;
        }

        if (!is_array($data = json_decode($response->getBody(), true))) {
            // todo handle error
            return false;
        }

        return isset($data['success']);
    }
}