<?php


use Rx\Observable;
use RX\ObserverInterface;
use RX\SchedulerInterface;
use React\Promise\Promise;
use React\Promise\Deferred;


use Peter\Observable\RxPHPCustomExpandSubscriber;


/**
 * merges pathValue into a cache
 */
function pathValueMerge($cache, $pathValue) {
    $refs = [];
    $values = [];
    $invalidations = [];
    $valueType = true;

    
    // The pathValue invalidation shape.
    if (isset($pathValue['invalidated'])) {
        $invalidations[] = ["path"=> $pathValue['path']];
        $valueType = false;
    }

    // References.  Needed for evaluationg suffixes in all three types, get,
    // call and set.
    else if ((isset($pathValue['value']) && isset($pathValue['value']['$type'])) && ($pathValue['value']['$type'] === 'ref')) {
        $refs[] = [
            "path" => $pathValue['path'],
            "value" => $pathValue['value']['value']
        ];
    }

    // Values.  Needed for reporting for call.
    else {
        $values[] = $pathValue;
    }

    print_r($values);

    // If the type of pathValue is a valueType (reference or value) then merge
    // it into the jsonGraph cache.
    if ($valueType) {
        innerPathValueMerge($cache, $pathValue);
    }
    $out = [
        "references" => $refs,
        "values" => $values,
        "invalidations" => $invalidations
    ];

    print_r($out);
    return $out;
};

function isMessage($message) {
    return false;
}

/**
 * takes the response from an action and merges it into the
 * cache.  Anything that is an invalidation will be added to
 * the first index of the return value, and the inserted refs
 * are the second index of the return value.  The third index
 * of the return value is messages from the action handlers
 *
 * @param {Object} cache
 * @param {Array} jsongOrPVs
 */
function mergeCacheAndGatherRefsAndInvalidations(
    $cache, $jsongOrPVs, $routerInstance
) {
    $references = [];
    $len = -1;
    $invalidations = [];
    $unhandledPaths = [];
    $messages = [];
    $values = [];

    

    // Go through each of the outputs from the route end point and separate out
    // each type of potential output.
    //
    // * There are values that need to be merged into the JSONGraphCache
    // * There are references that need to be merged and potentially followed
    // * There are messages that can alter the behavior of the
    //   recurseMatchAndExecute cycle.
    // * unhandledPaths happens when a path matches a route but the route does
    //   not match the entire path, therefore there is unmatched paths.
    foreach($jsongOrPVs as $jsongOrPV) {
        
        $refsAndValues = [];

        if (isMessage($jsongOrPV)) {
            $messages[] = $jsongOrPV;
        }

        //else if (isJSONG(jsongOrPV)) {
        //    refsAndValues = jsongMerge(cache, jsongOrPV, routerInstance);
        //}

        // Last option are path values.
        else {
            $refsAndValues = pathValueMerge($cache, $jsongOrPV);
        }

        print_r($refsAndValues);

        $refs = $refsAndValues['references'];
        $vals = $refsAndValues['values'];
        $invs = $refsAndValues['invalidations'];
        $unhandled = $refsAndValues['unhandledPaths'];

        if ($vals && count($vals)) {
            $values = $values.concat($vals);
        }

        if ($invs && count($invs)) {
            $invalidations = $invalidations.concat($invs);
        }

        if ($unhandled && count($unhandled)) {
            $unhandledPaths = $unhandledPaths.concat($unhandled);
        }

        if ($refs && count($refs)) {
            foreach($refs as $ref) {
                $references[] = $ref;
            }
        }
    }

    return [
        'invalidations' => $invalidations,
        'references' => $references,
        'messages' => $messages,
        'values' => $values,
        'unhandledPaths' => $unhandledPaths
    ];
}




/**
 * Custom Expand operator function for RxPHP.
 *
 * @template T
 *
 * @param callable(T, int): Observable $project The projection function.
 * @param int $concurrent The maximum number of concurrent inner observables.
 * @return callable(Observable): Observable
 */
function customExpand(callable $project, int $concurrent = PHP_INT_MAX): callable
{
    return function (Observable $source) use ($project, $concurrent): Observable {
        return Observable::create(function (ObserverInterface $observer, SchedulerInterface $scheduler) use ($source, $project, $concurrent) {
            $subscriber = new RxPHPCustomExpandSubscriber($observer, $project, $concurrent, $scheduler);
            // The subscription to the source observable
            $sourceDisposable = $source->subscribe($subscriber, $scheduler);
            $subscriber->setParentDisposable($sourceDisposable);

            // Return a disposable for the outer subscription
            return $subscriber; // The subscriber itself acts as the disposable for cleanup
        });
    };
}

/**
 * Converts a single-value Observable to a ReactPHP Promise.
 * Resolves with the last (and only) emitted value, or rejects on error/empty.
 */
function observableToPromise(Observable $observable): Promise
{
    $deferred = new Deferred();
    $lastValue = null;
    $hasValue = false;

    $observable->subscribe(
        function ($value) use (&$lastValue, &$hasValue) {
            $lastValue = $value;
            $hasValue = true;
        },
        function (\Throwable $error) use ($deferred) {
            $deferred->reject($error);
        },
        function () use ($deferred, &$lastValue, &$hasValue) {
            if ($hasValue) {
                $deferred->resolve($lastValue);
            } else {
                $deferred->reject(new \RuntimeException("Observable completed without emitting a value."));
            }
        }
    );

    return $deferred->promise();
}


/**
 * De PHP-vertaling van de JavaScript-functie `_recurseMatchAndExecute`.
 * @param callable $match Functie die paden matcht met routes.
 * @param callable $actionRunner Functie die acties uitvoert voor gematchte paden.
 * @param array $paths Initiële paden om te verwerken.
 * @param string $method De HTTP-methode (get, set, call).
 * @param object $routerInstance Router instantie met configuratie (bijv. maxRefFollow).
 * @param array $jsongCache De JsongCache die gemuteerd wordt.
 * @return array Het uiteindelijke resultaat met onverwerkte paden, invalidaties en de JsongCache.
 * @throws Throwable
 */
function _recurseMatchAndExecute(
    callable $match,
    callable $actionRunner,
    array $paths,
    string $method,
    object $routerInstance,
    array &$jsongCache // Passed by reference om mutatie toe te staan
) {
    $unhandledPaths = [];
    $invalidated = [];
    $reportedPaths = [];
    $currentMethod = $method;

   
    foreach($paths as $nextPaths) {
        

        if (!count($nextPaths)) {
            return [];
        }

        $result = $match($currentMethod, $nextPaths);

       
        if (isset($result['error'])) {
            return new Exception($result['error']);
        }
        
        $matchedResults = $result;

        // When there is explicitly not a match then we need to handle
        // the unhandled paths.
        if (!count($matchedResults)) {
            $unhandledPaths[] = $nextPaths;
            return [];
        }

        $results = runByPrecedence($nextPaths, $matchedResults, $actionRunner);
		
       

        $value = $results['value'];
        $suffix = $results['match']['suffix'];

        // TODO: MaterializedPaths, use result.path to build up a
        // "foundPaths" array.  This could be used to materialize
        // if that is the case.  I don't think this is a
        // requirement, but it could be.
        //if (!is_array($value)) {
            $value = array($value);
        //}

        

        $invsRefsAndValues = mergeCacheAndGatherRefsAndInvalidations(
            $jsongCache,
            $value,
            $routerInstance
        );

        $pathsToExpand = [];

        print_r($invsRefsAndValues );

        // Explodes and collapse the tree to remove
        // redundants and get optimized next set of
        // paths to evaluate.
        $optimizeResult = optimizePathSets(
            $jsongCache, 
            $pathsToExpand, 
            $routerInstance->maxRefFollow
        );

        print_r($optimizeResult);
        
        $endValue = array(); 
		////$runByPrecedence->map(function ($value) {
			
		//	 return $value;
		//});

		

        //print_r($runByPrecedence);
		/*$aaaa = $runByPrecedence->subscribe(
        function ($x) use ($jsongCache) {
			$jsongCache = $x;
            print_r($x);
			//return $x;
            echo 'Nexta: ', json_encode($x), PHP_EOL;
        },
        function ($ex) {
           // echo 'Error: ', $ex->getMessage(), " in: ", $ex->getFile(), " on Line: ", $ex->getLine(),  PHP_EOL;
        },
        function () {
           // echo 'Completed', PHP_EOL;
        }
    );
	var_dump($aaaa->dispose());*/

    }

	return $jsongCache;
   // print_r( $source);

    /*$customExpandedObservable = $customExpandOperator($source);
    $customExpandedObservable->reduce(function($acc, $x) {
            return $acc;
        }, null)->
        map(function() use ($unhandledPaths, $invalidated, $jsongCache, $reportedPaths) {
            return [
                "unhandledPaths" => $unhandledPaths,
                "invalidated" => $invalidated,
                "jsonGraph" => $jsongCache,
                "reportedPaths" => $reportedPaths
            ];
        });*/
    //print_r($source);
    //$expanded = $source->expand(function ($nextPaths) use ($unhandledPaths) {
    //    print_r($nextPaths);
    //    $unhandledPaths = [];
    //    $jsongCache = "smdghkjdfjghjkdfl";

    //    return [
    //        'unhandledPaths' => $unhandledPaths,
    //        'jsonGraph' => $jsongCache,
    //    ];
    //}, 1)->reduce(function ($acc, $x) {
     //   print_r($acc);
     //   return $acc;
    //})->map(function () use($unhandledPaths, $invalidated, $jsongCache, $reportedPaths) {
     //   echo "fff";
     //   return [
     //       'unhandledPaths' => $unhandledPaths,
     //       'invalidated' => $invalidated,
     //       'jsonGraph' => $jsongCache,
     //       'reportedPaths' => $reportedPaths,
     //   ];
   // });






   //return $source;

	
          

     
       

       // return [
       //     'unhandledPaths' => $unhandledPaths,
        //    'invalidated' => $invalidated,
        //    'jsonGraph' => [],
        //    'reportedPaths' => $reportedPaths,
       // ];

	

    
   
}

/**
 * De publieke functie `recurseMatchAndExecute`.
 * @param callable $match
 * @param callable $actionRunner
 * @param array $paths
 * @param string $method
 * @param object $routerInstance
 * @param array $jsongCache
 * @return array
 * @throws Throwable
 */
function recurseMatchAndExecute(
    callable $match,
    callable $actionRunner,
    array $paths,
    string $method,
    object $routerInstance,
    array $jsongCache // Initial copy, will be passed by reference to _recurseMatchAndExecute
){
    echo "eee";
    // We moeten een kopie van de cache doorgeven die dan door referentie wordt gemuteerd
    // in _recurseMatchAndExecute.
    $cacheCopy = $jsongCache;
    $d = _recurseMatchAndExecute(
        $match,
        $actionRunner,
        $paths,
        $method,
        $routerInstance,
        $cacheCopy
    );

    //print_r($d);
    return $d;
}


function optimizePathSets($cache, $paths, $maxRefFollow = 5) {
    $optimized = [];

    for ($i = 0, $len = count($paths); $i < $len; ++$i) {
        $error = optimizePathSet($cache, $cache, $paths[$i], 0, $optimized, [], $maxRefFollow);
        if ($error) {
            return ['error'=> $error ];
        }
    }
    return ['paths' => $optimized ];
}


/**
 * optimizes one pathSet at a time.
 */
function optimizePathSet(
    $cache, 
    $cacheRoot, 
    $pathSet,
    $depth, 
    $out, 
    $optimizedPath, 
    $maxRefFollow
) {

    // at missing, report optimized path.
    if ($cache === undefined) {
        $out[] = catAndSlice($optimizedPath, $pathSet, $depth);
        return;
    }

    // all other sentinels are short circuited.
    // Or we found a primitive (which includes null)
    if (
        $cache === null || 
        ($cache['$type'] && $cache['$type'] !== "ref") ||
        (gettype($cache) !== 'object')
    ) {
        return;
    }

    // If the reference is the last item in the path then do not
    // continue to search it.
    if ($cache['$type'] === "ref" && $depth === count($pathSet)) {
        return;
    }

    $keySet = $pathSet[$depth];
    $isKeySet = gettype($keySet) === 'object' && $keySet !== null;
    $nextDepth = $depth + 1;
    $iteratorNote = false;
    $key = $keySet;
    if ($isKeySet) {
        $iteratorNote = [];
        $key = iterateKeySet($keySet, $iteratorNote);
    }
    $next; $nextOptimized;
    do {
        $next = $cache[$key];
        $optimizedPathLength = count($optimizedPath);
        $optimizedPath[$optimizedPathLength] = $key;

        if ($next && $next['$type'] === "ref" && $nextDepth < count($pathSet)) {
            $refResults =
                followReference($cacheRoot, $next['value'], $maxRefFollow);
            if ($refResults['error']) {
                return $refResults['error'];
            }
            $next = $refResults['node'];
            // must clone to avoid the mutation from above destroying the cache.
            $nextOptimized = cloneArray($refResults['refPath']);
        } else {
            $nextOptimized = $optimizedPath;
        }

        $error = optimizePathSet($next, $cacheRoot, $pathSet, $nextDepth,
                        $out, $nextOptimized, $maxRefFollow);
        if ($error) {
            return $error;
        }
        

        if ($iteratorNote && !$iteratorNote['done']) {
            $key = iterateKeySet($keySet, $iteratorNote);
        }
    } while ($iteratorNote && !$iteratorNote['done']);
}


function innerPathValueMerge($cache, $pathValue) {
    print_r($pathValue);
    $path = $pathValue['path'];
    $curr = $cache;
    

    for ($i = 0, $len = count($path) - 1; $i < $len; ++$i) {
        $outerKey = $path[$i];

        // Setup the memo and the key.
        if ($outerKey && gettype($outerKey) === 'object') {
            $iteratorNote = [];
            $key = iterateKeySet($outerKey, $iteratorNote);
        } else {
            $key = $outerKey;
            $iteratorNote = false;
        }

        do {
            $next = $curr[$key];

            if (!$next) {
                $next = $curr[$key] = [];
            }

            if ($iteratorNote) {
                innerPathValueMerge(
                    $next, [
                        "path" => $path.slice($i + 1),
                        "value" => $pathValue['value']
                    ]);

                if (!$iteratorNote['done']) {
                    $key = iterateKeySet($outerKey, $iteratorNote);
                }
            }

            else {
                $curr = $next;
            }
        } while ($iteratorNote && !$iteratorNote['done']);

        // All memoized paths need to be stopped to avoid
        // extra key insertions.
        if ($iteratorNote) {
            return;
        }
    }


    // TODO: This clearly needs a re-write.  I am just unsure of how i want
    // this to look.  Plus i want to measure performance.
    $outerKey = $path[$i];

    $iteratorNote = [];
    $key = iterateKeySet($outerKey, $iteratorNote);

    do {

        $cloned = clone($pathValue['value']);
        $curr[$key] = $cloned;

        if (!$iteratorNote['done']) {
            $key = iterateKeySet($outerKey, $iteratorNote);
        }
    } while (!$iteratorNote['done']);
}