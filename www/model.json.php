<?php

include ("FalcorRouter.php");
include ("route.php");
include ("testRoute.php");

//print_r($_REQUEST);


//header('Content-Type: application/json; charset=UTF-8');

$route = new Route;

// match a request for the key "greeting"    
$route->route = "greeting";
// respond with a PathValue with the value of "Hello World."
$route->get = function() {
    return '{path:["greeting"], value: "Hello World"}';
};

$route2 = new TestRoute;

// match a request for the key "greeting"    
$route2->route = "testME";
// respond with a PathValue with the value of "Hello World."

$route3 = new Route;
// match a request for the key "greeting"    
$route3->route = "todos.name";
// respond with a PathValue with the value of "Hello World."
$route3->get = function() {
    return '{path:["todos.name"], value: "Hello World"}';
};



$routerArray = array($route, $route2, $route3);



$router = new FalcorRouter($routerArray);

$path = $_GET['paths'];
$paths = json_decode($path);
print_r($paths);
$myRoute = $router->get($paths);

//print_r($myRoute );
$obj1 = new stdClass;
$obj1->greeting = "Hello World";

$obj = new stdClass;
$obj->jsonGraph = $obj1;

//echo json_encode($obj);
echo json_encode($myRoute);
