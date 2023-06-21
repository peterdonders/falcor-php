<?php

use Peter\Parser;

define('ROUTE_ID', -3);

include("convertTypes.php");

function parseTree($routes) {
    
    $pTree = array();
    $parseMap = (object)[];
    foreach($routes as $route) {
        if (gettype($route->route) === 'string') {
            $route->prettyRoute = $route->route;
            $route->route = Parser::pathSyntax($route->route, true);
            
            convertTypes($route);
        }
        if (method_exists($route,'get')){
            $route->getId = 1;
        }
        if (method_exists($route,'set')){
            $route->setId = 2;
        }
        if (method_exists($route,'call')){
            $route->callId = 3;
        }
        //buildParseTree($pTree, $route, 0, []);
    }

    
   


    
    return $pTree;
}

function buildParseTree($node, $routeObject, $depth) {

    $route = $routeObject->route;
    $get =  method_exists($routeObject,'get');
    $set = method_exists($routeObject,'set');
    $call = method_exists($routeObject,'call');
    $el = $route[$depth];

   
    $isArray = is_array($el);
    $i = 0;
    
    do {
        $value = $el;
        $next  = (object)[];
        if ($isArray) {
            $value = $value[$i];
        }

       

        // There is a ranged token in this location with / without name.
        // only happens from parsed path-syntax paths.
        if (getType($value) === 'object') {
            $routeType = $value->type;
            $next = decendTreeByRoutedToken($node, $routeType, $value);
        }

        // This is just a simple key.  Could be a ranged key.
        else {
            $next = decendTreeByRoutedToken($node, $value);

            

            // we have to create a falcor-router virtual object
            // so that the rest of the algorithm can match and coerse
            // when needed.
            if ($next) {
                $route[$depth] = (object)[ 'type'=> $value, 'named' => false];
            }
            else {
                if (!isset($node[$value])) {
                    $node[$value] = (object)[];
                }
                
                $next = $node[$value];
                
            }
        }

        // Continue to recurse or put get/set.
        if ($depth + 1 === count($route)) {
            
            // Insert match into routeSyntaxTree
            $matchObject = isset($next->match) ? $next->match : (object)[];
            
            if (!isset($next->match)) {
                $next->match = $matchObject;
            }

            $matchObject->prettyRoute = $routeObject->prettyRoute;
            
            if ($get) {
                $matchObject->get = actionWrapper($route, $get);
                $matchObject->getId = $routeObject->getId;
            }
            if ($set) {
                $matchObject->set = actionWrapper($route, $set);
                $matchObject->setId = $routeObject->setId;
            }
            if ($call) {
                $matchObject->call = actionWrapper($route, $call);
                $matchObject->callId = $routeObject->callId;
            }
        } else {
            buildParseTree($next, $routeObject, $depth + 1);
        }

        

    } while ($isArray && ++$i < count($el));

    
}

/**
 * decends the rst and fills in any naming information at the node.
 * if what is passed in is not a routed token identifier, then the return
 * value will be null
 */
function decendTreeByRoutedToken($node, $value = null, $routeToken = null) {
    $next = null;

    var_dump($node);

    switch ($value) {
        case ' keys':
        case ' integers':
        case ' ranges':
            $next = $node[$value];
            if (!$next) {
                $next = $node[$value] = (object)[];
            }
            break;
        default:
            break;
    }
    if ($next && $routeToken) {
        // matches the naming information on the node.
        $next[' named'] = $routeToken->named;
        $next[' name'] = $routeToken->name;
    }

    return $next;
}

function actionWrapper() {
    return "a";
}