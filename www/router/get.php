
<?php


use Rx\Observable;
use React\EventLoop\Factory;
use Rx\Scheduler;


function outputToObservable($valueOrObservable) {
    $value = $valueOrObservable;

    // primitives, arrays.
    if (gettype($value) !== "object" || $value === null || is_array($value)) {
        return Observable::of($value);
    }

    // compatible observables, classic observables, promises.
    if (
        
        gettype($value->subscribe) === "function"
        || gettype($value->then) === "function"
    ) {
        return Observable::forkJoin($value);
    }

    // this will be jsong or pathValue at this point.
    return Observable::of($value);
};

function getAction($routerInstance, $matchAndPath, $jsongCache, $methodSummary) {
    $match = $matchAndPath['match'];
    //$out = $match['action']['call']($routerInstance, $matchAndPath['path']);
	
   

    $out = call_user_func(
        $match['action'], 
        array(
            $routerInstance, 
            $matchAndPath['path']
        )
    );

    $return = [
        'match' => $match,
        'value' => $out
    ];

    return $return;
    
	//return Rx\Observable::of(42);
    /*$out = outputToObservable($out);
	echo "aa111";
    if ($methodSummary) {
        $_out = $out;
        $out = Observable::defer(function () use ($routerInstance, $matchAndPath, $_out, $methodSummary) {
            $route = [
                "start"=> $routerInstance['_now()'],
                "route"=> $matchAndPath['match']['prettyRoute'],
                "pathSet" => $matchAndPath['path'],
                "results" => []
            ];
            $methodSummary['routes'][] = $route;
            return $_out->do(
                function ($response) {
                    $route['results'][] = [
                        "time" => "TODO NOW",
                        "value" => $response
                    ];
                },
                function (Throwable $err) {
                    $route['error'] = $err;
                    $route['end'] = "TODO NOW";
                },
                function () {
                   $route['end'] = "TODO NOW";
                }
            )->subscribe();
        });
    }

    return $out->doOnCompleted(
		function() {
        //map(normalizeJsongOrPV($matchAndPath['path'], false)),
        //map(function($jsonGraphOrPV) {
        //    return [$matchAndPath['match'], $jsonGraphOrPV];
       // })
	   echo "comp";
		}
    );*/

	//echo "Hello";
}



// Placeholder for runGetAction
function runGetAction($routerInstance, $jsongCache, $methodSummary) {
    // Simulate some action
    return function ($matchAndPath) use ($routerInstance, $jsongCache, $methodSummary) {
        return getAction($routerInstance, $matchAndPath, $jsongCache, $methodSummary);
    };
}



// Placeholder for normalizePathSets
function normalizePathSets($paths) {
    // Simulate path normalization
    return $paths;
}

// Placeholder for materialize
function materialize($router, $normPS, $jsonGraphEnvelope) {
    // Simulate materialization of the JSON Graph
    return $jsonGraphEnvelope;
}

// Placeholder for mCGRI (mergeCacheAndGatherRefsAndInvalidations)
function mCGRI(&$jsonGraph, $details, $router) {
    // Simulate merging cache and gathering refs/invalidations
    foreach ($details as $detail) {
        if (isset($detail['jsonGraph'])) {
            $jsonGraph = array_merge_recursive($jsonGraph, $detail['jsonGraph']);
        }
    }
}

// Placeholder for MaxPathsExceededError
class MaxPathsExceededError extends Exception {
    public function __construct($message = "Max paths exceeded", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

// Placeholder for getPathsCount
function getPathsCount($pathSets) {
    // Simple count for demonstration
    return count($pathSets);
}



/**
 * A no-operation function.
 * @return callable
 */
function noop(): callable {
    return function() {};
}

/**
 * Adapts an old-style observer to a new-style observer.
 * In a real RxPHP scenario, this might involve checking for specific interfaces.
 *
 * @param object $observer The observer to adapt.
 * @return object The adapted observer.
 */
function toRxNewObserver(object $observer): object {
    // Check if the observer already has the 'new' RxPHP methods (next, error, complete)
    // This is a simplified check; real RxPHP would likely use interface checking.
    if (
        !method_exists($observer, 'onNext') &&
        !method_exists($observer, 'onError') &&
        !method_exists($observer, 'onCompleted')
    ) {
        return $observer; // Already new style
    }

    // It's an old-style observer; create a new one that delegates.
    return new class($observer) {
        private $destination;

        public function __construct(object $observer) {
            $this->destination = $observer;
        }

        public function next($value): void {
            if (method_exists($this->destination, 'onNext')) {
                $this->destination->onNext($value);
            } else {
                noop()(); // Call noop if onNext doesn't exist
            }
        }

        public function error(Throwable $error): void {
            if (method_exists($this->destination, 'onError')) {
                $this->destination->onError($error);
            } else {
                noop()(); // Call noop if onError doesn't exist
            }
        }

        public function complete(): void {
            if (method_exists($this->destination, 'onCompleted')) {
                $this->destination->onCompleted();
            } else {
                noop()(); // Call noop if onCompleted doesn't exist
            }
        }
    };
}

function rxNewToRxNewAndOld($rxNewObservable) {
   
   /* $_subscribe = $rxNewObservable->subscribe;

    $rxNewObservable->subscribe = function($observerOrNextFn, $errFn, $compFn) {
        $subscription;
        if (gettype($observerOrNextFn) !== "object" || $observerOrNextFn === null) {
            $subscription = $_subscribe.call(
                $this,
                $observerOrNextFn,
                $errFn,
                $compFn
            );
        } else {
            $observer = toRxNewObserver($observerOrNextFn);
            $subscription = $_subscribe->call($this, $observer);
        }

        $_unsubscribe = $subscription->unsubscribe;

        $subscription->unsubscribe = $subscription->dispose = function() {
            $this->isDisposed = true;
            $_unsubscribe.call($this);
        };

        return $subscription;
    };*/

    return $rxNewObservable;
}



trait Get {

    /**
     * The router get function
     * @param array $paths The paths to retrieve.
     * @return Observable An Observable that emits the JSON Graph response.
     */
	public function get(array $paths)
	{

		

		$router = $this; // Reference to the current router instance

		return Observable::defer(function () use ($router, $paths) {
			echo "aa";
			$methodSummary = null;
			if ($router->methodSummaryHook) {
				$methodSummary = [
					'method' => 'get',
					'pathSets' => $paths,
					'start' => $router->_now(),
					'results' => [],
					'routes' => []
				];
			}


			$result = Observable::defer(function () use ($router, $paths, &$methodSummary) {
				echo "bbb";
				$jsongCache = [];
                $action = runGetAction($router, $jsongCache, $methodSummary);
                $normPS = normalizePathSets($paths);


				if (getPathsCount($normPS) > $router->maxPaths) {
					echo "cccc";
					return Observable::error(new MaxPathsExceededError());
				}
               
				$sources = recurseMatchAndExecute($router->matcher, $action, $normPS,
                                        'get', $router, $jsongCache);


				
				print_r($sources);

				
			});

			return $result;
			
        });

    }

}