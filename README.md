**Usage**

- Simple

`$url = http://uri.li/cJjN`

```php
$acurl = new aCurl($url);
// or
$acurl = new aCurl();
$acurl->setUrl($url);

// Execute cURL request
$acurl->run();

print_r($acurl->getRequestHeaders());
print_r($acurl->getResponseHeaders());

/* Result
Array
(
    ...
    [host] => uri.li
    [user_agent] => aCurl/v1.0
)

Array
(
    ...
    [content_length] => 0
    [content_type] => text/html
    [location] => http://google.com/
    [pragma] => no-cache
    [status_code] => 301
    [status_text] => Moved Permanently
    [set_cookie] => Array
        (
            [0] => ...
            [1] => ...
        )
    [vary] => Accept-Encoding
)
*/

// Check response status
print $acurl->getStatusCode(); // 301
print $acurl->getStatusText(); // Move Permanently

// Work with response headers
$responseHeaders = $acurl->getResponseHeaders();
if ($responseHeaders['status_code'] >= 400) {
    printf('Error: %s', $responseHeaders['status_text']);
}

// Work with response body
$responseBody = $acurl->getResponseBody();
$dom = new Dom($responseBody); // trivial class just for example
print $dom->getElementById('foo')->getAtrribute('src');
```

- Set & get options

Note: See for available CURLOPT_* constants: http://tr.php.net/curl_setopt

```php
$acurl = new aCurl($url, [
    'followlocation' => 1,
    // ...
]);
// or
$acurl->setOptions([
    'followlocation' => 1,
    // ...
]);
$acurl->setOption('followlocation', 1);
// or
$acurl->setOption(CURLOPT_FOLLOWLOCATION, 1);
// or
$acurl->setFollowlocation(1);

print $acurl->getOption('followlocation'); // 1
print $acurl->getOption(CURLOPT_FOLLOWLOCATION);
print $acurl->getFollowlocation();

pritn $acurl->getOptions(); // [...)
```

- Set & get method

```php
$acurl = new aCurl($url);
$acurl->setMethod(aCurl::METHOD_POST);

print $acurl->getMethod() // POST
```

- Set URL params

```php
$acurl = new aCurl($url);
$acurl->setUrlParam('foo', 1);
// or
$acurl->setUrlParam(array(
    'foo' => 1,
    'bar' => 'The bar!'
));

print $acurl->getUrlParam('foo'); // 1
print $acurl->getUrlParams(); // array(...)

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
$acurl->setRequestHeader(array('X-Foo-2: foo2'));
$acurl->setRequestHeader(array('X-Foo-3' => 'foo3'));

// set body (while posting data)
// Note: Doesn't work if aCurl method is GET
$acurl->setRequestBody('foo=1&bar=The+bar%21');
$acurl->setRequestBody(array(
    'foo' => 1,
    'bar' => 'The bar!'
));

// get raw equest
print $acurl->getRequest();
/*
GET /cJjN HTTP/1.1
User-Agent: aCurl/v1.0
Host: uri.li
...
*/

// get request body
$acurl->getRequestBody();

// get request header
$acurl->getRequestHeader('host');
// get request headers
$acurl->getRequestHeaders(); // array(...)
// get request headers raw?
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
$acurl->getResponseHeader('status_code');
// get response headers
$acurl->getResponseHeaders(); // array(...)
// get response headers raw?
$acurl->getResponseHeaders(true);

// not storing response headers & body
$acurl->storeResponseHeaders(false);
$acurl->storeResponseBody(false);
```

- Auto closing cURL handler (default=true)

```php
// Block auto close
$acurl->autoClose(false);

do {
    $retry = 0;
    // exec
    $acurl->run();
    if ($acurl->getResponseBody() == '') {
        sleep(1);
        ++$retry;
    }
} while ($retry < 3);

// remember to call this
// forgot anyway? it's ok! aCurl::__destruct() will close it...
$acurl->close();
```

- Simple upload

```php
$acurl = new aCurl('http://local/upload.php');
$acurl->setMethod(aCurl::METHOD_POST);
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