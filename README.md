**Usage**

- Simple

`$url = http://uri.li/cJjN`

```php
$acurl = new ACurl\ACurl($url);
// or set url later
$acurl = new ACurl\ACurl();
$acurl->setUrl($url);

// Execute cURL request
$acurl->run();

print_r($acurl->getRequestHeaders());
print_r($acurl->getResponseHeaders());

/* Result
Array
(
    [host] => uri.li
    [user_agent] => ACurl/v0.3 (+http://github.com/qeremy/acurl)
    ...
)

Array
(
    [_status] => 301 Moved Permanently
    [_status_code] => 301
    [_status_text] => Moved Permanently
    [content_length] => 0
    [content_type] => text/html
    [location] => http://google.com/
    [pragma] => no-cache
    [set_cookie] => Array
        (
            [0] => ...
            [1] => ...
        )
    [vary] => Accept-Encoding
    ...
)
*/

// Check response status
print $acurl->getStatus();     // 301 Moved Permanently
print $acurl->getStatusCode(); // 301
print $acurl->getStatusText(); // Moved Permanently

// Print response body
print $acurl->getResponseBody();
```

- Set & get options

Note: See for available CURLOPT_* constants: http://tr.php.net/curl_setopt

```php
$acurl = new ACurl\ACurl($url, [
    'followlocation' => 1,
    // ...
]);
// or set options later (all available)
$acurl->setOption('followlocation', 1);
$acurl->setOption(CURLOPT_FOLLOWLOCATION, 1);
$acurl->setOptions(['followlocation' => 1]);
$acurl->setFollowlocation(1);

// all available
print $acurl->getOption('followlocation'); // 1
print $acurl->getOption(CURLOPT_FOLLOWLOCATION);
print $acurl->getFollowlocation();

// all optiions
pritn $acurl->getOptions(); // [...]
```

- Set & get method

```php
$acurl = new ACurl\ACurl($url);
$acurl->setMethod(ACurl\ACurl::METHOD_POST);

print $acurl->getMethod() // POST
```

- Set URL params

```php
$acurl = new ACurl\ACurl($url);
$acurl->setUrlParam('foo', 1);
// or
$acurl->setUrlParam([
    'foo' => 1,
    'bar' => 'The bar!'
]);

print $acurl->getUrlParam('foo'); // 1
print $acurl->getUrlParams();     // [...]

// $acurl->getUrl() -> <$url>?foo=1&bar=The+bar%21
```

- Get cURL info (after `run()`)

```php
$info = $acurl->getInfo();
print $info['url'];
// or
print $acurl->getInfo('url');
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