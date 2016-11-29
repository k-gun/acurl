<?php
ini_set('display_errors', 'On');
ini_set('error_reporting', E_ALL);

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

$client = new ACurl\Client('get >> http://localhost/');
// $client = new ACurl\Client('get >> http://www.google.com.tr/');
$client->send();

prs($client->request->toString());
prs($client->response->toString());
// prs($client->request->getMethod());
// prs($client->request->getHeadersRaw());
// prs($client->response->getHeadersRaw());
