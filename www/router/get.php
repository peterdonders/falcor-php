
<?php


use Rx\Observable;
use React\EventLoop\Factory;
use Rx\Scheduler;




// Placeholder for runGetAction
function runGetAction($router, &$jsongCache, $methodSummary) {
    // Simulate some action
    return function($paths) use (&$jsongCache) {
        // This function would typically process the paths and fill jsongCache
        return ['jsonGraph' => ['data' => 'mock_data'], 'unhandledPaths' => []];
    };
}

// Placeholder for recurseMatchAndExecute
/*function recurseMatchAndExecute($matcher, $action, $normPS, $method, $router, &$jsongCache) {
    // This function would simulate the core routing logic
    // For now, it returns a simple structure. In a real scenario, this would
    // involve complex matching and execution against the router's routes.
    return (object)[
        'jsonGraph' => ['example' => 'data'],
        'invalidations' => [],
        'missing' => [],
        'unhandledPaths' => []
    ];
}*/

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

// Placeholder for outputToObservable - simulates converting output to an Observable
function outputToObservable($output) {
    // In a real RxPHP scenario, this would wrap the output in an Observable
    return new class($output) {
        private $value;
        public function __construct($value) { $this->value = $value; }
        public function map($callback) { return $this->of($callback($this->value)); }
        public function defaultIfEmpty($defaultValue) { return $this->value === null ? $this->of($defaultValue) : $this->of($this->value); }
        public function of($value) { return new class($value) { private $v; public function __construct($v) { $this->v = $v; } public function map($cb) { return $this->of($cb($this->v)); } public function defaultIfEmpty($def) { return $this->v === null ? $this->of($def) : $this->of($this->v); } }; }
    };
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
    public function get(array $paths): Observable
    {

        $router = $this; // Reference to the current router instance

        return rxNewToRxNewAndOld(Observable::defer(function () use ($router, $paths) {
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
                $jsongCache = [];
                $action = runGetAction($router, $jsongCache, $methodSummary);
                $normPS = normalizePathSets($paths);

                // For debugging, in a real scenario you'd use a proper logger
                 echo "Action created.\n";

                if (getPathsCount($normPS) > $router->maxPaths) {
                    throw new MaxPathsExceededError();
                }

                return Observable::of(recurseMatchAndExecute($router->matcher, $action, $normPS,
                                            'get', $router, $jsongCache))->
                    // Turn it (jsongGraph, invalidations, missing, etc.) into a
                    // jsonGraph envelope
                    flatMap(function ($details) use ($router, $paths, &$methodSummary) {
                         echo "Details from recurseMatchAndExecute: " . json_encode($details) . "\n";
                        
                        $out = [
                            'jsonGraph' => $details['jsonGraph']
                        ];

                        // If the unhandledPaths are present then we need to
                        // call the backup method for generating materialized.
                        if (!empty($details->unhandledPaths) && $router->_unhandled) {
                            $unhandledPaths = $details->unhandledPaths;

                            // The 3rd argument is the beginning of the actions
                            // arguments, which for get is the same as the
                            // unhandledPaths.
                            return outputToObservable(
                                $router->_unhandled->get($unhandledPaths))->

                                // Merge the solution back into the overall message.
                                map(function ($jsonGraphFragment) use (&$out, $unhandledPaths, $router) {
                                    mCGRI($out['jsonGraph'], [[
                                        'jsonGraph' => $jsonGraphFragment->jsonGraph,
                                        'paths' => $unhandledPaths
                                    ]], $router);
                                    return $out;
                                })->
                                defaultIfEmpty($out);
                        }

                        return Observable::of($out);
                    })->
                    // We will continue to materialize over the whole jsonGraph message.
                    // This makes sense if you think about pathValues and an API that if
                    // ask for a range of 10 and only 8 were returned, it would not
                    // materialize for you, instead, allow the router to do that.
                    map(function ($jsonGraphEnvelope) use ($router, $normPS) {
                        return materialize($router, $normPS, $jsonGraphEnvelope);
                    });
            });

            if ($router->methodSummaryHook || $router->errorHook) {
                $result = $result->
                    do(function ($response) use ($router, &$methodSummary) {
                        if ($router->methodSummaryHook) {
                            $methodSummary['results'][] = [
                                'time' => $router->_now(),
                                'value' => $response
                            ];
                        }
                    }, function (Throwable $err) use ($router, &$methodSummary) {
                        if ($router->_methodSummaryHook) {
                            $methodSummary['end'] = $router->_now();
                            $methodSummary['error'] = $err;
                            ($router->_methodSummaryHook)($methodSummary);
                        }
                        if ($router->_errorHook) {
                            ($router->_errorHook)($err);
                        }
                    }, function () use ($router, &$methodSummary) {
                        if ($router->_methodSummaryHook) {
                            $methodSummary['end'] = $router->_now();
                            ($router->_methodSummaryHook)($methodSummary);
                        }
                    });
            }
            return $result;
        }));
    }

}