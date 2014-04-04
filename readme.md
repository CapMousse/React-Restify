#React-Restify 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/CapMousse/React-Restify/badges/quality-score.png?s=6d1986dbc42d5fc5a1e23134896b284797de29b0)](https://scrutinizer-ci.com/g/CapMousse/React-Restify/)

> RESTful api made easy for [ReactPHP](http://nodephp.org/), seriously.

##Instalation
In your `composer.json`

    "require"       : {
        "react/restify": "dev-master"
    },


##Create server

Here is an exemple of a simple HTTP server replying to all get call like `http://127.0.0.1:1337/hello/you`

```php
require 'vendor/autoload.php';

$server = new React\Restify\Server("MyAPP", "0.0.0.1");

$server->get('/hello/[name]:any', function ($request, $response, $args) {
    $response->write("Hello ".$args['name']);
});

$runner = new React\Restify\Runner($server);
$runner->listen(1337);
```

**More examples can be found on the example directory**

## Design goals

*React-Restify* was primary made to build RESTful api easily. It can be used like *Silex*, but without the framework part.

Next part will be to support Sockets, Upgrade Requests... to create a real time API server.

##Licence

MIT, see LICENCE file