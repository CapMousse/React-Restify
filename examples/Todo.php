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
    if (!isset($request->name)) {
        return $response->setStatus(500);
    }

    $todoList[] = ["name" => $request->name, "value" => "waiting"];
    $id = count($todoList)-1;

    $response->writeJson((object)array("id" => $id));
    $next();
});

//Get a single todo
$server->get('/todo/{id}', function ($request, $response, $next) use (&$todoList) {
    if (!isset($todoList[$request->id])) {
        return $response->setStatus(500);
    }

    $response->writeJson((object)$todoList[$request->id]);
    $next();
});

//Update a todo
$server->put('/todo/{id}', function ($request, $response, $next) use (&$todoList) {
    if (!isset($todoList[$request->id]) || (!$request->name && !$request->value)) {
        return $response->setStatus(500);
    }

    if ($request->name) {
        $todoList[$request->id]["name"] = $request->name;
    }

    if ($request->value) {
        $todoList[$request->id]["value"] = $request->value;
    }

    $response->writeJson((object)$todoList[$requests->id]);
    $next();
});

//Delete a todo
$server->del('/todo/{id}', function ($request, $response, $next) use (&$todoList) {
    if (!isset($todoList[$request->id])) {
        return $response->setStatus(500);
    }

    unset($todoList[$request->id]);
});

$runner = new CapMousse\ReactRestify\Runner($server);
$runner->listen("1337");
