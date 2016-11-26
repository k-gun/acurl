<?php
class Test
{
    public function __construct()
    {
        ini_set("display_errors", "On");
        ini_set("error_reporting", E_ALL);

        require __dir__ ."/inc.php";
        require __dir__ ."/../ACurl/Autoload.php";

        ACurl\Autoload::register();
    }

    public static function run()
    {
        $test = new Test();
        $testMethods = get_class_methods($test);
        foreach ($testMethods as $testMethod) {
            if (substr($testMethod, 0, 5) == "test_") {
                echo "Running: ", $testMethod, "()...\n";
                sleep(1);
                call_user_func_array([$test, $testMethod], []);
            }
        }
    }

    public static function echo(...$args)
    {
        foreach ($args as $arg) {
            if (is_null($arg)) {
                echo "null";
            } elseif (is_bool($arg)) {
                echo ($arg) ? "true" : "false";
            } else {
                print_r($arg);
            }
        }
        echo "\n\n";
    }

    public function test_requestMethod()
    {
        $client = new ACurl\Client("get >> http://localhost/");
        self::echo("Request method is 'GET'? ",
            $client->request->getMethod() == "GET");
    }

    public function test_requestUri()
    {
        $client = new ACurl\Client("get >> http://localhost/");
        self::echo("Request URI is 'http://localhost/'? ",
            $client->request->getUri() == "http://localhost/");
    }

    public function test_requestHeader()
    {
        $client = new ACurl\Client("get >> http://localhost/");
        $client->send();
        self::echo("Request header[host] is 'localhost'? ",
            $client->request->getHeader("host") == "localhost");
    }

    public function test_responseStatus()
    {
        $client = new ACurl\Client("get >> http://localhost/");
        $client->send();
        self::echo("Response status is '200 OK'? ",
            $client->response->getStatus() == "200 OK");
    }

    public function test_responseStatusCode()
    {
        $client = new ACurl\Client("get >> http://localhost/");
        $client->send();
        self::echo("Response status[code] is '200'? ",
            $client->response->getStatusCode() === 200);
    }

    public function test_responseStatusText()
    {
        $client = new ACurl\Client("get >> http://localhost/");
        $client->send();
        self::echo("Response status[text] is 'OK'? ",
            $client->response->getStatusText() == "OK");
    }
}

Test::run();
