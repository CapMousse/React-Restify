<?php

require '../vendor/autoload.php';

$server = new React\Restify\Server("SmallTodoServer", "0.0.0.1");

$todoList = array(
    array("name" => "Build a todo list example", "value" => "done")
);

$server->get('/', function ($request, $response, $args) use (&$todoList) {
    $response->writeJson((object)$todoList);
});

$server->post('/', function ($request, $response, $args) use (&$todoList) {
    if (!isset($args['name'])) {
        return $response->setStatus(500);
    }

    $todoList[] = array($args['name'] => false);
    $id = count($todoList)-1;

    $response->writeJson((object)array("id" => $id));
});

$server->get('/todo/[id]:num', function ($request, $response, $args) use (&$todoList) {
    if (!isset($todoList[$args['id']])) {
        return $response->setStatus(500);
    }

    $response->writeJson((object)$todoList[$args['id']]);
});
$server->put('/todo/[id]:num', function ($request, $response, $args) use (&$todoList) {
    if (!isset($todoList[$args['id']]) || (!isset($args['name']) && !isset($args['value']))) {
        return $response->setStatus(500);
    }

    if (isset($args['name'])) {
        $todoList[$args['id']]["name"] = $args['name'];
    }

    if (isset($args['value'])) {
        $todoList[$args['id']]["value"] = $args['value'];
    }

    $response->writeJson((object)$todoList[$args['id']]);
});
$server->del('/todo/[id]:num', function ($request, $response, $args) use (&$todoList) {
    if (!isset($todoList[$args['id']])) {
        return $response->setStatus(500);
    }

    unset($todoList[$args['id']]);
});

$runner = new React\Restify\Runner($server);
$runner->listen("1337");