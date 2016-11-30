ACurl: Aims to simplify your cURL operations in PHP.

### In a Nutshell

```php
$client = new ACurl\Client('github.com');
$client->send();

echo $client->response->getStatus();
```

### Usage

```php
// simply
$client = new ACurl\Client('get >> https://github.com/');

// with uri & uri params
$client = new ACurl\Client('https://github.com', [
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
$client->send();

echo $client->request->gettHeadersRaw();
echo $client->response->gettHeadersRaw();

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

### Set & get options

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

### Set & get method

```php
// in init
$client = new ACurl\ACurl("post >> $url");
// or later
$client->request->setMethod(ACurl\Http\Request::METHOD_POST);

echo $client->getMethod() // POST
```

### Set URL & URL params

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

### Get cURL info (after `send()`)

```php
echo $client->getInfo('url');
// or
$info = $client->getInfo();
echo $info['url'];
```

- Request

```php
// set headers (all available)
$acurl->setRequestHeader('X-Foo-1: foo1');
$acurl->setRequestHeader('X-Foo-1', 'foo1');
$acurl->setRequestHeaders(['X-Foo-2: foo2']);
$acurl->setRequestHeaders(['X-Foo-3' => 'foo3']);

// set body (while posting data)
// Note: useless if method is GET
$acurl->setRequestBody('foo=1&bar=The+bar%21');
$acurl->setRequestBody([
    'foo' => 1,
    'bar' => 'The bar!'
]);

// get raw equest
print $acurl->getRequest();
/*
GET /cJjN HTTP/1.1
User-Agent: ACurl/v1.0
Host: uri.li
...
*/

// get request body
$acurl->getRequestBody();

// get request header
$acurl->getRequestHeader('host');
// get request headers
$acurl->getRequestHeaders(); // [...]
// get request headers raw
$acurl->getRequestHeaders(true);
```

- Response

```php
// get raw response
$acurl->getResponse();
/*
HTTP/1.1 301 Moved Permanently
Server: nginx
Date: Thu, 22 Aug 2013 22:34:22 GMT
Content-Type: text/html
Content-Length: 0
...
*/

// get response body
$acurl->getResponseBody();

// get response header
$acurl->getResponseHeader('_status_code');
// get response headers
$acurl->getResponseHeaders(); // [...]
// get response headers raw
$acurl->getResponseHeaders(true);

// not storing response body/headers
$acurl->storeResponseBody(false);
$acurl->storeResponseHeaders(false);
```

- Auto closing cURL handler (default=true)

```php
// Block auto close
$acurl->autoClose(false);

$retry = 0;
do {
    // exec
    $acurl->run();
    if (($body = $acurl->getResponseBody()) != '') {
        break;
    }
    // wait a sec
    sleep(1);
    // go on
    ++$retry;
} while ($retry < 3);

// remember to call this
// forgot anyway? it's ok! __destruct() will close it...
$acurl->close();
```

- Simple upload

```php
$acurl = new ACurl\ACurl('http://local/upload.php');
$acurl->setMethod(ACurl\ACurl::METHOD_POST);
$acurl->setRequestBody(array(
    'fileName' => 'myfile-2.txt',
    'fileData' => file_get_contents('./myfile-1.txt'),
));
$acurl->run();

// upload.php
$fileName = $_POST['fileName'];
$fileData = $_POST['fileData'];
file_put_contents("./$fileName", $fileData);
```

- Error handling

```php
if ($acurl->isFail()) {
    printf('Error! Code[%d] Text[%s]',
        $acurl->getFailCode(), $acurl->getFailText());
}
```
