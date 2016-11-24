<?php
require __dir__ .'/inc.php';
require __dir__ .'/../ACurl/Autoload.php';

ACurl\Autoload::register();

// $client = new ACurl\Client([
//     'method' => 'GET',
//     'uri' => 'www.google.com',
//     'uriParams' => ['a' => 1],
//     'body' => ['foo' => 111]
// ]);
// $client->send();

$client = new ACurl\Client();
// $client->send("GET https://google.com", [], null, [], ['X-foo' => 123, 'x-A'=>"aaa"]);
// $client->send("GET https://www.facebook.com/");
$client->send("https://ipinfo.io/");

print $client->response->getBody();
