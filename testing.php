<?php

require __DIR__.'/vendor/autoload.php';

use Guzzle\Http\Client;

// create our http client (Guzzle)
$client = new Client('http://rest-realworld.local', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$nickname = 'GeekDev'.random_int(1,199);
$data = [
    'nickname' => $nickname,
    'tagLine' => 'A test programmer!',
    'avatarNumber' => random_int(1,5)
];

$request = $client->post('/api/programmers', null, json_encode($data));
$response = $request->send();


$url = $response->getHeader('Location');
$request = $client->get($url);
$response = $request->send();


$request = $client->get('/api/programmers');
$response = $request->send();



echo $response;
echo "\n\n";
