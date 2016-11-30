ACurl: Aims to simplify your cURL operations in PHP.

### In a Nutshell

```php
$client = new ACurl\Client('github.com');
$client->send();
// or simply exec a GET request
$client->get();

echo $client->response->getStatus();
```

### Usage

```php
// simply
$client = new ACurl\Client('get >> https://github.com/');

// with uri & uri params
$client = new ACurl\Client('get >> https://github.com', [
    'uriParams' => ['foo' => 1]
]);

// or all-inc.
$client = new ACurl\Client(null, [
    'method'    => 'post',
    'uri'       => 'https://github.com',
    'uriParams' => ['foo' => 1],
    'headers'   => ['X-Foo' => 'Yes'],
    'cookies'   => ['sid' => 'abc123'],
    'body'      => ['lorem' => 'ipsum', 'dolor' => '...'],
    // cURL options
    'options'   => [CURLOPT_FOLLOWLOCATION => true]
]);

// execute request
$response = $client->send();

echo $client->request->gettHeadersRaw();
echo $client->response->gettHeadersRaw();
// or
echo $response->getHeadersRaw();

// GET / HTTP/1.1
// Accept: */*
// Host: github.com
// User-Agent: ACurl/v2.0.0 (+https://github.com/k-gun/acurl)
//
// HTTP/1.1 200 OK
// Cache-Control: no-cache
// Content-Security-Policy: default-src 'none'; ...
// ...

// response status
echo $client->response->getStatus();     // 200
echo $client->response->getStatusCode(); // 200 OK
echo $client->response->getStatusText(); // OK

// response body
echo $client->response->getBody();
```

### Set & Get Options

Note: See for available CURLOPT_* constants: http://tr.php.net/curl_setopt

```php
// set
$client->setOption(CURLOPT_FOLLOWLOCATION, 1);
// set all
$client->setOptions([CURLOPT_FOLLOWLOCATION => 1, ...]);

// get
echo $client->getOption(CURLOPT_FOLLOWLOCATION);
// get all
echo $client->getOptions(); // [...]
```

### Set URL & URL Params

```php
$client = new ACurl\Client($url);
$client->request->setUriParam('foo', 1);
// or
$client->request->setUriParams([
    'foo' => 1,
    'bar' => 'The bar!'
]);

echo $client->request->getUriParam('foo'); // 1
echo $client->request->getUriParams();     // [...]

// $client->request->getUri() -> <$url>?foo=1&bar=The+bar%21
```

### Get cURL Info (After `send()`)

```php
echo $client->getInfo('url');
// or
$info = $client->getInfo();
echo $info['url'];
```

### Request

```php
// send (curl exec)
$client->send();
$client->send([any $body = null, [, array $uriParams = null, [, array $headers = null, [, array $cookies = null]]]]);

// get raw request
echo $client->request;
echo $client->request->toString();
echo $client->getRequest();
echo $client->getRequest()->toString();

// GET / HTTP/1.1
// Accept: */*
// Host: github.com
// User-Agent: ACurl/v2.0.0 (+https://github.com/k-gun/acurl)
// ...
// [body]

// method
$client->request->setMethod(ACurl\Http\Request::METHOD_POST);
$client->request->getMethod(); // string

// uri
$client->request->setUri(...);
$client->request->getUri();     // string
$client->request->getUriFull(); // string

// uri params
$client->request->setUriParam('foo', 1);
$client->request->setUriParams(['foo', 1]);
$client->request->getUriParam('foo'); // string|int
$client->request->getUriParams();

// body
$client->request->setBody('foo=1&bar=The+bar%21');
$client->request->setBody([
    'foo' => 1,
    'bar' => 'The bar!'
]);

$client->request->getBody();

// headers
$client->request->setHeader('host', '...');
$client->request->setHeaders(['host', '...']);

$client->request->getHeader('host');  // string
$client->request->getHeaders();       // array
$client->request->getHeadersRaw();    // string
$client->request->getHeadersString(); // string

// cookies
$client->request->setCookie('foo', 1);
$client->request->setCookies(['foo', 1]);

$client->request->getCookie('foo');   // string
$client->request->getCookies();       // array
$client->request->getCookiesString(); // string
```

### Response

```php
// get raw response
echo $client->response;
echo $client->response->toString();
echo $client->getResponse();
echo $client->getResponse()->toString();

// HTTP/1.1 200 OK
// Cache-Control: no-cache
// Content-Security-Policy: default-src 'none';
// ...
// [body]

// body
$client->response->getBody();

// header
$client->response->getHeader('_status'); // int
$client->response->getHeaders();         // [...]
$client->response->getHeadersRaw();      // string
$client->response->getHeadersString();   // string

// status
$client->response->getStatus();     // string
$client->response->getStatusCode(); // int
$client->response->getStatusText(); // string

// shorcut checkers
$client->response->isSuccess();  // bool
$client->response->isFailure();  // bool
$client->response->isRedirect(); // bool
```

### Auto Closing cURL Handler (default=true)

```php
// Block auto close
$client->setAutoClose(false);

$retry = 0;
do {
    // exec
    $client->send();
    if ('' != ($body = $client->response->getBody())) {
        break;
    }
    // wait a sec
    sleep(1);
    // go on
    ++$retry;
} while ($retry < 3);

// remember to call this
// forgot anyway? it's ok! __destruct() will close it...
$client->close();
```

### Simple Upload

```php
$client = new ACurl\ACurl('post >> http://local/upload.php', [
    'body' => [
        'fileName' => 'myfile-2.txt',
        'fileData' => file_get_contents('./myfile-1.txt'),
    ]
]);
$client->send();

// upload.php
$fileName = $_POST['fileName'];
$fileData = $_POST['fileData'];
file_put_contents("./$fileName", $fileData);
```

### Errors

```php
try {
    // ...
    $client->send();
} catch (\Throwable $e) {
    echo $e->getMessage();
}
``

### Bonus (Shortcut Methods Instead of `send()`)

```php
$client->get([array $uriParams = null, [, array $headers = null, [, array $cookies = null]]]);
$client->post($body = null, [array $uriParams = null, [, array $headers = null, [, array $cookies = null]]]);
$client->put($body = null, [array $uriParams = null, [, array $headers = null, [, array $cookies = null]]]);
$client->patch($body = null, [array $uriParams = null, [, array $headers = null, [, array $cookies = null]]]);
$client->delete([array $uriParams = null, [, array $headers = null, [, array $cookies = null]]]);
```
