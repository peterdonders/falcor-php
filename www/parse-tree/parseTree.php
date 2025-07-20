<?php

use Peter\Parser;

define('ROUTE_ID', -3);

include("convertTypes.php");




function parseTree($routes) {
    
    $pTree = [];
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

        $parseMap = setHashOrThrowError($parseMap, $route);

		
		
		buildParseTree($pTree, $route, 0);


		
    }

    
   


    
    return $pTree;
}



function buildParseTree(&$node, $routeObject, $depth) {

	$route = $routeObject->route;
	$get =  method_exists($routeObject,'get');
	$set = method_exists($routeObject,'set');
	$call = method_exists($routeObject,'call');
	$el = $route[$depth];

	$isArray = is_array($el);
	$i = 0;
	

	do {
		$value = $el;
		$next = null;
        if ($isArray) {
            $value = $value[$i];
        }

		// There is a ranged token in this location with / without name.
        // only happens from parsed path-syntax paths.
        if (gettype($value) === 'object') {
			
            $routeType = $value->type;
            $next = decendTreeByRoutedToken($node, $value, $routeType);
		}

		// This is just a simple key.  Could be a ranged key.
		else {
			$next = decendTreeByRoutedToken($node, $value);

			// we have to create a falcor-router virtual object
			// so that the rest of the algorithm can match and coerse
			// when needed.
			if ($next) {
				$route[$depth] = ["type"=> $value, "named"=> false];

				if (!isset($node[$value])) {
					
                    $node[$value] = [];
                }
                $next = $node[$value];
			}
			else {
                if (!isset($node[$value])) {
				
                    $node[$value] = [];
                }

				
				
                $next = $node[$value];
            }
        }

		

		 // Continue to recurse or put get/set.
        if ($depth + 1 === count($route)) {
			
			// Insert match into routeSyntaxTree
            $matchObject = $next[Keys::match] ?? [];
            
	
			$matchObject['prettyRoute'] = $routeObject->prettyRoute;

			

			if ($get) {
                $matchObject['get'] = actionWrapper($route, $routeObject->get);
                $matchObject['getId'] = $routeObject->getId;
            }
            if ($set) {
                $matchObject['set'] = actionWrapper($route, $routeObject->set);
                $matchObject['setId'] = $routeObject->setId;
            }
            if ($call) {
                $matchObject['call'] = actionWrapper($route, $routeObject->call);
                $matchObject['callId'] = $routeObject->callId;
            }

			if (!isset($next[Keys::match])) {
                $next[Keys::match] = $matchObject;
            }

			$node[$value] = $next;
		}
		else {

			buildParseTree($next, $routeObject, $depth + 1);
			
			
			if (is_string($value) ) {
				$node[$value] = $next;
			}
			else {
				$node = $next;
			}
			
			
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
	$canNext = false;

	switch ($routeToken) {
		case Keys::keys:
		case Keys::integers:
		case Keys::ranges:
			$canNext = true;
			break;
		default:
		break;
	}

	if ($canNext && $value) {
		$next = [];
		// matches the naming information on the node.
		$next[Keys::named] = $value->named;
		$next[Keys::name] = $value->name;
	}

	return $next;
}

function isPathValue($x) {
    return false;
}

function convertPathToRoute($path, $route) {
	$matched = [];
	// Always use virtual path since path can be longer since
	// it contains suffixes.
	for ($i = 0, $len = count($route); $i < $len; ++$i) {
		if(isset($route[$i]['type'])) {
			echo "TODO convertPathToRoute";
			/*
			 var virt = route[i];
            switch (virt.type) {
                case Keys.ranges:
                    matched[i] =
                        convertPathKeyToRange(path[i]);
                    break;
                case Keys.integers:
                    matched[i] =
                        convertPathKeyToIntegers(path[i]);
                    break;
                case Keys.keys:
                    matched[i] =
                        convertPathKeyToKeys(path[i]);
                    break;
                default:
                    throw new Error('Unknown route type.');
            }
            if (virt.named) {
                matched[virt.name] = matched[matched.length - 1];
            }
				*/
		}

		// Dealing with specific keys or array of specific keys.
		// If route has an array at this position, arrayify the
		// path[i] element.
		else {
			if (is_array($route[$i]) && !is_array($path[$i])) {
                $matched[] = [$path[$i]];
            }
			else {
                $matched[] = $path[$i];
            }
		}
    }

    return $matched;
}

function actionWrapper($route, $action) {
	return function($matchedPath) use ($route, $action) {
        $len = -1;
		$restOfArgs = array_slice(func_get_args(), 1);
        $isJSONObject = !is_array($matchedPath);

		// A set uses a json object
        if ($isJSONObject) {
            //restOfArgs = [];
            $convertedArguments = $matchedPath;
        }

		 // Could be an array of pathValues for a set operation.
        else if (isPathValue($matchedPath[0])) {
            $convertedArguments = [];

			foreach($matchedPath as $pV) {
            	$pV->path = convertPathToRoute($pV->path, $route);
                $convertedArguments[++$len] = $pV;
            }
        }

		 // else just convert and assign
        else {
            $convertedArguments = convertPathToRoute($matchedPath, $route);
        }

		return call_user_func(
			$action, 
			array_merge($convertedArguments, $restOfArgs));

	};
}

/**
 * creates a hash of the virtual path where integers and ranges
 * will collide but everything else is unique.
 */
function getHashesFromRoute($route, $depth = 0, $hashes = [], $hash = []) {
  

    $routeValue = $route[$depth];
    $isArray = is_array($routeValue);
    $length = 0;
    if ($isArray) {
        $length = count($routeValue);
    }
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
            $hashes[] = getHashesFromRoute($route, $depth + 1, $hashes, $hash);
            
        }

        // Or just add it to hashes
        else {
            $hashes[] = $hash;
        }
    } while ($isArray && ++$idx < $length);

    
    
    return $hashes;
}


function implode_recursive(string $separator, array $array)
{
	return array_map(function($element) use($separator) {
		$glue = [];
		foreach($element as $value) {
			if (gettype($value) == "array") {
				if (gettype(current($value))  == "array") {
					foreach($value as $b) {
						$glue[] = implode($separator, $b);
					}
				}
				else {
					$glue[] = implode($separator, $value);
				}
			}
			else {
				$glue[] = $value;
			}
		}

		return $glue;

	}, $array);
}


/**
 * ensure that two routes of the same precedence do not get
 * set in.
 */
function setHashOrThrowError($parseMap, $routeObject) {
    $route = $routeObject->route;

    $get = method_exists($routeObject,'get');
    $set = method_exists($routeObject,'set');
    $call = method_exists($routeObject,'call');

    


	$hash = getHashesFromRoute($route);
	

    /*$hash1 = array_map(function($element) {
        $glue = [];
        foreach($element as $value) {
           
            if (gettype($value) == "array") {

                if (gettype(current($value))  == "array") {
                    foreach($value as $b) {
                        $glue[] = implode(",", $b);
                    }
                }
                else {
                    $glue[] = implode(",", $value);
                }

                
            }
            else {
                $glue[] = $value;
            }
        }

       

        return $glue;

    }, $hash);*/

	$hash1 = implode_recursive(",", $hash);
	
	$hash2 = current($hash1);
	
	foreach($hash2 as $hashRoute) {
		
		if (
			$get && isset($parseMap[$hashRoute . 'get']) ||
			$set && isset($parseMap[$hashRoute . 'set']) ||
			$call && isset($parseMap[$hashRoute . 'call'])
		) {
			throw new Exception(
				'Two routes cannot have the same precedence or path.' .
				' ' .
				//prettifyRoute($route)
				$route
			); 
		}

	

		if ($get) {
			$parseMap[$hashRoute . 'get'] = true;
		}

		if ($set) {
			$parseMap[$hashRoute . 'set'] = true;
		}

		if ($call) {
			$parseMap[$hashRoute . 'call'] = true;
		}
	}

	return $parseMap;
}

