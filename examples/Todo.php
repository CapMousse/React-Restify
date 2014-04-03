<?php

require '../vendor/autoload.php';

$server = new CapMousse\ReactRestify\Server("SmallTodoServer", "0.0.0.1");

$todoList = array(
    array("name" => "Build a todo list example", "value" => "done")
);

//List all todo
$server->get('/', function ($request, $response, $next) use (&$todoList) {
    $response->writeJson((object)$todoList);
    $next();
});

//Create a new todo
$server->post('/', function ($request, $response, $next) use (&$todoList) {
    if (!$request->name) {
        $response->setStatus(500);
        return $next();
    }

    $todoList[] = ["name" => $request->name, "value" => "waiting"];
    $id = count($todoList)-1;

    $response->writeJson((object)array("id" => $id));
    $next();
})->after(function($request, $response, $route) use (&$todoList){
    echo "\nA new todo as been created at id ".(count($todoList)-1);
});


$server->group('todo', function($routes) use (&$todoList){
    //Get a single todo
    $routes->get('{id}', function ($request, $response, $next) use (&$todoList) {
        if (!isset($todoList[$request->id])) {
            $response->setStatus(500);
            return $next();
        }

        $response->writeJson((object)$todoList[$request->id]);
        $next();
    })->where('id', '[0-9]+');

    //Update a todo
    $routes->put('{id}', function ($request, $response, $next) use (&$todoList) {
        if (!isset($todoList[$request->id]) || (!$request->name && !$request->value)) {
            $response->setStatus(500);
            $next();
        }

        if ($request->name) {
            $todoList[$request->id]["name"] = $request->name;
        }

        if ($request->value) {
            $todoList[$request->id]["value"] = $request->value;
        }

        $response->writeJson((object)$todoList[$request->id]);
        $next();
    })->after(function($request, $response, $route){
        echo "\nTodo ".$request->id." as been modified";
    });

    //Delete a todo
    $routes->delete('{id}', function ($request, $response, $next) use (&$todoList) {
        if (!isset($todoList[$request->id])) {
            $response->setStatus(500);
            $next();
        }

        unset($todoList[$request->id]);
        $response->writeJson((object)['error' => false]);
        $next();
    })->after(function($request, $response, $route){
        echo "\nTodo ".$request->id." as been deleted";
    });

})->after(function($request, $response, $route) use (&$todoList){
    echo "\nTodo access";
});

$server->on('NotFound', function($request, $response, $next){
    $response->write('You fail, 404');
    $response->setStatus(404);

    $next();
});

$runner = new CapMousse\ReactRestify\Runner($server);
$runner->listen("1337");
