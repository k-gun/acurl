<?php
require_once '../aCurl/aCurlException.php';
require_once '../aCurl/aCurl.php';

// $url = 'http://uri.li/cJjN';
$url = 'http://dev.local/acurl/test/b.php';

// $prm = array('a'=>array(1,2), 'b'=>3);

$aCurl = new aCurl($url);
// $aCurl->setMethod(aCurl::METHOD_PUT);

// $aCurl->storeResponseHeaders(false);
// $aCurl->storeResponseBody(false);

// $aCurl->setRequestHeader('X-Foo-1: foo1');
// $aCurl->setRequestHeader(array('X-Foo-2: foo2'));
// $aCurl->setRequestHeader(array('X-Foo-3' => 'foo3'));

// $aCurl->setUrlParams($prm);
// $aCurl->setRequestBody($prm);

// $aCurl->setCookie('a=1;');
// $aCurl->setOption('cookie', 'a=1;');

// $aCurl->setOption('header', 0);
// $aCurl->setOption('nobody', 0);
// $aCurl->setOption('returntransfer', 0);

// $aCurl->setTimeoutMs(1000);
      // ->setMaxRecvSpeedLarge(111);
// $aCurl->set_timeoutMs(1000);

// $aCurl->setFollowlocation(1);

// $aCurl->setUrlParam(array(
//     'foo' => 1,
//     'bar' => 'The bar!'
// ));

// $aCurl->setMethod(aCurl::METHOD_POST);
// $aCurl->setRequestBody(array(
//     'fileName' => 'myfile-2.txt',
//     'fileData' => file_get_contents('./myfile-1.txt'),
// ));

// $aCurl->run();

// pre($aCurl->getMaxRecvSpeedLarge());

// pre($aCurl->getUrl());
// pre($aCurl->getRequestHeaders());
// pre($aCurl->getResponseHeaders());

// pre("---Response Body---");
// pre($aCurl->getResponseBody());

function pre($s, $e = 0) {
    printf('<pre>%s</pre>', print_r($s, 1));
    if ($e) exit;
}