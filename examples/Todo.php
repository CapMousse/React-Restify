<?php

require '../vendor/autoload.php';

use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;

class Test {
    public function blop () {
        print_r("blop");
    }
}

class TodoListController {
    private static $todoList = array(
        array("name" => "Build a todo list example", "value" => "done")
    );

    public function listTodo(Request $request, Response $response, Test $test)
    {
        $test->blop();
        $response
            ->writeJson((object)self::$todoList)
            ->end();
    }

    public function getTodo(Request $request, Response $response, $id)
    {
        if (!isset(self::$todoList[$id])) {
            $response
                ->setStatus(404)
                ->end();
            return;
        }

        $response
            ->writeJson((object)self::$todoList[$id])
            ->end();
    }

    public function createTodo(Request $request, Response $response)
    {
        if (!$request->name) {
            $response
                ->setStatus(500)
                ->end();
            return;
        }

        self::$todoList[] = ["name" => $request->name, "value" => $request->value ? $request->value : "waiting"];
        $id = count(self::$todoList)-1;

        $response
            ->writeJson((object)array("id" => $id))
            ->end();
    }

    public function updateTodo(Request $request, Response $response, $id)
    {
        if (!isset(self::$todoList[$id]) || (!$request->name && !$request->value)) {
            $response
                ->setStatus(500)
                ->end();
            return;
        }

        if ($request->name) {
            self::$todoList[$id]["name"] = $request->name;
        }

        if ($request->value) {
            self::$todoList[$id]["value"] = $request->value;
        }

        $response
            ->writeJson((object)self::$todoList[$request->id])
            ->end();
    }

    public function deleteTodo(Request $request, Response $response, $id)
    {
        if (!isset(self::$todoList[$id])) {
            $response
                ->setStatus(500)
                ->end();
            return;
        }

        unset(self::$todoList[$id]);
        $response
            ->writeJson((object)['error' => false])
            ->end();
    }

    public function afterCreateTodo()
    {
        echo "\nTodo " . (count(self::$todoList)-1) . " created";
    }

    public function afterUpdateTodo(Request $request)
    {
        echo "\nTodo ".$request->id." as been modified";
    }
}

$server = new CapMousse\ReactRestify\Server("SmallTodoServer", "0.0.0.1");

$server->use(function($next) {
    print_r("test");
    $next();
});

$server->add(Test::class);

//List all todo
$server->get('/', 'TodoListController@listTodo');

//Create a new todo
$server
    ->post('/', 'TodoListController@createTodo')
    ->after('TodoListController@afterCreateTodo');


$server->group('todo', function ($routes) {
    //Get a single todo
    $routes
        ->get('{id}', 'TodoListController@getTodo')
        ->where('id', '[0-9]+');

    //Update a todo
    $routes
        ->put('{id}', 'TodoListController@updateTodo')
        ->after('TodoListController@afterUpdateTodo');

    //Delete a todo
    $routes
        ->delete('{id}', 'TodoListController@deleteTodo')
        ->after(function(Request $request, Response $response){
            echo "\nTodo ".$request->id." as been deleted";
        });

})->after(function(Request $request, Response $response){
    echo "\nTodo access";
});

$server->listen("1337");
