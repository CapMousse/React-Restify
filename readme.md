#React-Restify 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/CapMousse/React-Restify/badges/quality-score.png?s=6d1986dbc42d5fc5a1e23134896b284797de29b0)](https://scrutinizer-ci.com/g/CapMousse/React-Restify/)

> RESTful api made easy for [ReactPHP](http://nodephp.org/), seriously.

##Instalation
Via composer

``` bash
    $ composer require capmousse/react-restify
```


##Create server

Here is an exemple of a simple HTTP server replying to all get call like `http://127.0.0.1:1337/hello/you`

``` php
require 'vendor/autoload.php';

$server = new CapMousse\ReactRestify\Server("MyAPP", "0.0.0.1");

// Middleware
$server->use(function ($request, $next) {
	print_r($request->getMethod());
	$next();
});

// Dependency injection
$server->add(\Foo\Bar::class)

$server->get('/hello/{name}', function ($request, $response, \Foo\Bar $bar, $name) {
    $response
    	->write("Hello {$name}")
    	->end();

    $bar->foobar();
});

$server->listen(1337);
```

To create a secure HTTPS server, you need to give all your cert files to the server :

``` php
$server->listen(1337, "[::1]", [
    'local_cert' => __DIR__ . 'localhost.pem'
]);
```
More examples can be found on the example directory like the **Todo** example.

## Controller, Middleware and Dependency injection

React-Restify support Controller call, middleware *à la express* and Dependency Injection.

``` php

use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;

class Foo {
    public function bar() {
        echo "Do something";
    }
}

class FooBar {
    public function baz (Response $response, Foo $foo) {
        $foo->bar();
        $response->end()
    }
}

$server->add(Foo::class);

$server->use(function ($request, $next) {
    echo $request->httpRequest->getPath();
});

$server->get('/', 'FooBar@baz');
```

## Design goals

*React-Restify* was primary made to build RESTful api easily. It can be used like *Silex* or *Express*.

Next part will be to support Sockets, Upgrade Requests... to create a real time API server.

##Licence

MIT, see LICENCE file