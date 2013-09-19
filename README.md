**Usage**

- Simple

`$url = http://uri.li/cJjN`

```php
$aCurl = new aCurl($url);
// or
$aCurl = new aCurl();
$aCurl->setUrl($url);

// Execute cURL request
$aCurl->run();

print_r($aCurl->getRequestHeaders());
print_r($aCurl->getResponseHeaders());

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
print $aCurl->getStatusCode(); // 301
print $aCurl->getStatusText(); // Move Permanently

// Work with response headers
$responseHeaders = $aCurl->getResponseHeaders();
if ($responseHeaders['status_code'] >= 400) {
    printf('Error: %s', $responseHeaders['status_text']);
}

// Work with response body
$responseBody = $aCurl->getResponseBody();
$dom = new Dom($responseBody); // trivial class just for example
print $dom->getElementById('foo')->getAtrribute('src');
```

- Set & get options

Note: See for available CURLOPT_* constants: http://tr.php.net/curl_setopt

```php
$aCurl = new aCurl($url, array(
    'followlocation' => 1,
    ...
));
// or
$aCurl->setOption(array(
    'followlocation' => 1,
    ...
));
$aCurl->setOption('followlocation', 1);
// or
$aCurl->setOption(CURLOPT_FOLLOWLOCATION, 1);
// or
$aCurl->setFollowlocation(1);

print $aCurl->getOption('followlocation'); // 1
print $aCurl->getOption(CURLOPT_FOLLOWLOCATION);
print $aCurl->getFollowlocation();

pritn $aCurl->getOptions(); // array(...)
```

- Set & get method

```php
$aCurl = new aCurl($url);
$aCurl->setMethod(aCurl::METHOD_POST);

print $aCurl->getMethod() // POST
```

- Set URL params

```php
$aCurl = new aCurl($url);
$aCurl->setUrlParam('foo', 1);
// or
$aCurl->setUrlParam(array(
    'foo' => 1,
    'bar' => 'The bar!'
));

print $aCurl->getUrlParam('foo'); // 1
print $aCurl->getUrlParams(); // array(...)

// $aCurl->getUrl() -> <$url>?foo=1&bar=The+bar%21
```

- Get cURL info (after `run()`)

```php
$info = $aCurl->getInfo();
print $info['url'];
// or
print $aCurl->getInfo('url');
```

- Request

```php
// set headers
$aCurl->setRequestHeader('X-Foo-1: foo1');
$aCurl->setRequestHeader(array('X-Foo-2: foo2'));
$aCurl->setRequestHeader(array('X-Foo-3' => 'foo3'));

// set body (while posting data)
// Note: Doesn't work if aCurl method is GET
$aCurl->setRequestBody('foo=1&bar=The+bar%21');
$aCurl->setRequestBody(array(
    'foo' => 1,
    'bar' => 'The bar!'
));

// get raw equest
print $aCurl->getRequest();
/*
GET /cJjN HTTP/1.1
User-Agent: aCurl/v1.0
Host: uri.li
...
*/

// get request body
$aCurl->getRequestBody();

// get request header
$aCurl->getRequestHeader('host');
// get request headers
$aCurl->getRequestHeaders(); // array(...)
// get request headers raw?
$aCurl->getRequestHeaders(true);
```

- Response

```php
// get raw response
$aCurl->getResponse();
/*
HTTP/1.1 301 Moved Permanently
Server: nginx
Date: Thu, 22 Aug 2013 22:34:22 GMT
Content-Type: text/html
Content-Length: 0
...
*/

// get response body
$aCurl->getResponseBody();

// get response header
$aCurl->getResponseHeader('status_code');
// get response headers
$aCurl->getResponseHeaders(); // array(...)
// get response headers raw?
$aCurl->getResponseHeaders(true);

// not storing response headers & body
$aCurl->storeResponseHeaders(false);
$aCurl->storeResponseBody(false);
```

- Auto closing cURL handler (default=true)

```php
// Block auto close
$aCurl->autoClose(false);

do {
    $retry = 0;
    // exec
    $aCurl->run();
    if ($aCurl->getResponseBody() == '') {
        sleep(1);
        ++$retry;
    }
} while ($retry < 3);

// remember to call this
// forgot anyway? it's ok! aCurl::__destruct() will close it...
$aCurl->close();
```

- Simple upload

```php
$aCurl = new aCurl('http://local/upload.php');
$aCurl->setMethod(aCurl::METHOD_POST);
$aCurl->setRequestBody(array(
    'fileName' => 'myfile-2.txt',
    'fileData' => file_get_contents('./myfile-1.txt'),
));
$aCurl->run();

// upload.php
$fileName = $_POST['fileName'];
$fileData = $_POST['fileData'];
file_put_contents("./$fileName", $fileData);
```

- Error handling

```php
if ($aCurl->isFail()) {
    printf('Error! Code[%d] Text[%s]',
        $aCurl->getFailCode(), $aCurl->getFailText());
}
```