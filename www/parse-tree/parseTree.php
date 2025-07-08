<?php

use Peter\Parser;

define('ROUTE_ID', -3);

include("convertTypes.php");

function parseTree($routes) {
    
    $pTree = array();
    $parseMap = [];
    foreach($routes as $route) {
        // converts the virtual string path to a real path with
        // extended syntax on.
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

        setHashOrThrowError($parseMap, $route);


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
            //$next = $node[$value];
           // if (!$next) {
                //$next = $node[$value] = (object)[];
            //}
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

/**
 * creates a hash of the virtual path where integers and ranges
 * will collide but everything else is unique.
 */
function getHashesFromRoute($route, $depth = 0, $hashes = [], $hash = []) {
  

    $routeValue = $route[$depth];
    $isArray = is_array($routeValue);
    $length = $isArray && count($routeValue) || 0;
    $idx = 0;


    if (gettype($routeValue) === 'object' && !$isArray) {
        $value = $routeValue->type;
    }

    else if (!$isArray) {
        $value = $routeValue;
    }

    do {
        if ($isArray) {
            $value = $routeValue[$idx];
        }

        if ($value === Keys::integers || $value === Keys::ranges) {
            $hash[$depth] = '__I__';
        }

        else if ($value === Keys::keys) {
            $hash[$depth] ='__K__';
        }

        else {
            $hash[$depth] = $value;
        }

        // recurse down the routed token
        if ($depth + 1 !== count($route)) {
            getHashesFromRoute($route, $depth + 1, $hashes, $hash);
        }

        // Or just add it to hashes
        else {
            $hashes[] = $hash;
        }
    } while ($isArray && ++$idx < $length);

    return $hashes;
}


/**
 * ensure that two routes of the same precedence do not get
 * set in.
 */
function setHashOrThrowError($parseMap, $routeObject) {
    $route = $routeObject->route;
    //$get = $routeObject->get;
    //$set = $routeObject->set;
    //$call = $routeObject->call;


    $hash = getHashesFromRoute($route);

    print_r($hash);
/*
    .
        map(function ($hash) { return $hash.join(','); }).
        forEach(function ($hash) {
            if (get && parseMap[hash + 'get'] ||
                set && parseMap[hash + 'set'] ||
                    call && parseMap[hash + 'call']) {
                throw new Error(errors.routeWithSamePrecedence + ' ' +
                               prettifyRoute(route));
            }
            if (get) {
                parseMap[hash + 'get'] = true;
            }
            if (set) {
                parseMap[hash + 'set'] = true;
            }
            if (call) {
                parseMap[hash + 'call'] = true;
            }
        });*/
}

