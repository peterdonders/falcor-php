<?php
set_time_limit(50000);
error_reporting(E_ALL);
ini_set("display_errors", 1);

include("./vendor/autoload.php");
include ("route.php");
include "myRouter.php";
use Peter\Parser;

/*


*/
$parseArgs = array (
    "jsonGraph" => true,
    "callPath"=>true,
    "arguments"=>true,
    "pathSuffixes"=>true,
    "paths"=>true
);


function requestToContext() {

    $parseArgs = array (
        "jsonGraph" => true,
        "callPath"=>true,
        "arguments"=>true,
        "pathSuffixes"=>true,
        "paths"=>true
    );

    if (get_request_method() === 'POST') {
        $request = cleanInputs($_POST);
        // The request is using the POST method
    }
    else {
        $request = cleanInputs($_GET);
    }

    
    $context = [];
    if ($request) {
        
        foreach($request as $key => $arg) {
            if (isset($parseArgs[$key]) && $arg) {
               $context[$key] = json_decode($arg);
            } else {
                $context[$key] = $arg;
            }
        };
    }
   
    return $context; 

    
}


function get_request_method() {
    return $_SERVER['REQUEST_METHOD'];
}


function cleanInputs($data) {
    $clean_input = array();
	if(is_array($data)) {
        foreach($data as $k => $v) {
            $clean_input[$k] = cleanInputs($v);
		}
	}
	else {
       $data = strip_tags($data);
        $clean_input = trim($data);
    }

    return $clean_input;
}


function dataSourceRoute() {
    $context = requestToContext();

    // probably this should be sanity check function?
    if (count($context) === 0) {
        return "Request not supported";
    }
    
    if (!isset($context['method']) || $context['method'] === "") {
        return "No query method provided";
    }
    
    //if (typeof dataSource[context.method] === "undefined") {
    //    return res.status(500).send("Data source does not implement the requested method");
    //}

  

    if ($context['method'] === "set") {
        $obs = "dataSource[context.method](context.jsonGraph)";
    }
    else if ($context['method'] === "call") {
        $obs = "dataSource[context.method](context.callPath, context.arguments, context.pathSuffixes, context.paths)";
    }
    else {
        $obs = "dataSource[".$context['method']."](".json_encode($context['paths']).")";
    }

    return $obs;

}




//print_r(dataSourceRoute()); 

$route = new Route;

// match a request for the key "greeting"    
$route->route = "greeting";
// respond with a PathValue with the value of "Hello World."
$route->get = function() {
    return "Hello World";
};



$route2 = new Route;

// match a request for the key "greeting"    
$route2->route = "titlesById[{integers:titleIds}]['userRating', 'rating']";
// respond with a PathValue with the value of "Hello World."
$route2->get = function($pathSet) {
    return "Hello World";
};



$route3 = new Route;

// match a request for the key "greeting"    
$route3->route = "todos.name";
// respond with a PathValue with the value of "Hello World."
$route3->get = function() {
    return ['name 1', 'name 2'];
};





$router = new Router(array($route, $route2, $route3));

print_r($router);

$context = requestToContext();

//print_r($context);

$obs = $router->{$context['method']}($context['paths']);

$obs->subscribe(function($jsonGraphEnvelope) {
    print_r($jsonGraphEnvelope);
}, function($err) {
            print_r($err);
        });
