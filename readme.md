#React-Restify

> RESTful api made easy for [ReactPHP](http://nodephp.org/), seriously.

React-Restify is a small framework inspired from [Node-Restify](http://mcavage.github.com/node-restify/) builded to easily create RESTful api.

##Instalation
In your `composer.json`


    "require"       : {
        "php": ">=5.3.2",
        "react/restify": "dev-master"
    },


##Create server
```php
require 'vendor/autoload.php';

$server = new React\Restify\Server("MyAPP", "0.0.1");

$server->get('/hello/[name]:any', function ($request, $response, $args) {
    $response->write("Hello ".$args['name']);
});

$runner = new React\Restify\Runner($server);
$runner->listen(1337);
```

Licence
---

The MIT License (MIT) Copyright (c) 2012 Jérémy Barbe

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.