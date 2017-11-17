<?php

namespace KnpU\CodeBattle\Tests\Controller\Api;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use PHPUnit_Framework_TestCase;

class ProgrammerControllerTest extends PHPUnit_Framework_TestCase
{
    public function testPOST()
    {
        // create our http client (Guzzle)
        $client = new Client('http://rest-realworld.local', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $nickname = 'GeekDev' . random_int(1, 199);
        $data = [
            'nickname' => $nickname,
            'tagLine' => 'A test programmer!',
            'avatarNumber' => random_int(1, 5)
        ];

        $request = $client->post('/api/programmers', null, json_encode($data));
        /**@var Response $response */
        $response = $request->send();


        $this->assertEquals($response->getStatusCode(), 201, 'DID not return correct statuscode.');
        $this->assertTrue($response->hasHeader('Location'));
        // response contains json with nickname key
        $this->assertArrayHasKey('nickname', json_decode($response->getBody(true), true));

    }

}