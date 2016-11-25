<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

require __dir__ .'/inc.php';
require __dir__ .'/../ACurl/Autoload.php';

ACurl\Autoload::register();

// $client = new ACurl\Client(null, [
//     'method' => 'GET',
//     'uri' => 'www.google.com',
//     'uriParams' => ['a' => 1],
//     'body' => ['foo' => 111]
// ]);
// $client->send();

// $client = new ACurl\Client('get >> http://localhost/');
$client = new ACurl\Client('http://google.com:5984/');
$client->setOption(CURLOPT_CONNECTTIMEOUT, 1);
$client->get();

prs($client->request->getHeadersRaw());
prs($client->response->getHeadersRaw());
var_dump($client->getFailText());
