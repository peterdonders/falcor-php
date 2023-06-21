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


$routerArray = array($route, $route2);



$router = new FalcorRouter($routerArray);

$path = $_GET['paths'];
$paths = json_decode($path);
$myRoute = $router->get($paths);

//print_r($myRoute );
$obj1 = new stdClass;
$obj1->greeting = "Hello World";

$obj = new stdClass;
$obj->jsonGraph = $obj1;

//echo json_encode($obj);
echo json_encode($myRoute);
