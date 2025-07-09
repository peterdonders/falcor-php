<?php

define("PREFIX", chr((int) 80));

include ("./operations/matcher.php");
include("./router/get.php");

use PhpParser\Node\Expr\Cast\Object_;

use Rx\Observable;
use React\EventLoop\Factory;
use Rx\Scheduler;

include("parse-tree/parseTree.php");
include("pathSyntax/path-syntax.php");

define("MAX_REF_FOLLOW", 50);
define("MAX_PATHS", 9000);

class Router {

	use Get;

    private $routes;
    
    private $rst;
    private $matcher;
    private $debug;
    private $pathErrorHook;
    private $errorHook;
    public $methodSummaryHook = null;
    private $now;
    public $maxRefFollow;
    public $maxPaths;

	public function __construct($routes, array $options = []) {
		$this->routes = $routes;
		$this->rst = parseTree($routes);
		//$this->matcher = matcher($this->rst);
		$this->setOptions($options);

		$loop = Factory::create();

		//You only need to set the default scheduler once
		Scheduler::setDefaultFactory(function() use($loop){
    		return new Scheduler\EventLoopScheduler($loop);
		});

    }



	private function setOptions(array $options) {

		$noOp = function(){};
		$defaultNow = function () {
			return date("Y-m-d H:i:s");
		};
		
		$opts = $options ?? [];

		$this->debug = $opts['debug'] ?? false;
		$this->pathErrorHook = $noOp;
		//$this->pathErrorHook = $opts['hooks']['pathError'] ?? $noOp;
		$this->errorHook = null;
		//$this->errorHook = $opts['hooks']['error'] ?? null;
		$this->methodSummaryHook = null;
		//$this->methodSummaryHook = $opts['hooks']['methodSummary'] ?? null;
		$this->now = $defaultNow;
		//$this->now = $opts['hooks']['now'] ?: $opts['now'] ?: $defaultNow;
		$this->maxRefFollow = $opts['maxRefFollow'] ?? MAX_REF_FOLLOW;
		$this->maxPaths = $opts['maxPaths'] ?? MAX_PATHS;
	}

	private function normalize($range) {
		$from = $range->from || 0;
		$to = null;
		if (gettype($range->to) === 'number') {
			$to = $range->to;
		}
		else {
			$to = $from + $range->length - 1;
		}

		return (object) array('to' => $to, 'from'=> $from);
	}

	/**
	 * warning:  This mutates the array of arrays.
	 * It only converts the ranges to properly normalized ranges
	 * so the rest of the algos do not have to consider it.
	 */
	private function normalizePathSets($path) {
		foreach($path as $key => $i) {
			// the algo becomes very simple if done recursively.  If
			// speed is needed, this is an easy optimization to make.
			if (is_array(value: $key)) {
				$path = $this->normalizePathSets($key);
			}
			else if (gettype($key) === 'object') {
				$path[$i] = $this->normalize($path[$i]);
			}
		}
		
		return $path;
	}

	private function getPathsCount($pathSets) {
	
		return count($pathSets);
    //return pathSets.reduce(function(numPaths, pathSet) {
//        return numPaths + falcorPathUtils.pathCount(pathSet);
  //  }, 0);
//}
	}

	private function runGetAction($jsongCache, $methodSummary) {
		return function ($matchAndPath) use ($jsongCache, $methodSummary) {
			return $this->getAction($matchAndPath, $jsongCache, $methodSummary);
		};
	}
	
	private function getAction($matchAndPath, $jsongCache, $methodSummary) {
		//print_r($matchAndPath);
		//print_r($jsongCache);
		//print_r($methodSummary);
	}

	private function recurseMatchAndExecute($match, $actionRunner, $paths,$method, $routerInstance, $jsongCache) {

		$unhandledPaths = [];
		$invalidated = [];
		$reportedPaths = [];
		$currentMethod = $method;

		$source = Observable::fromArray($paths)->expand(
			function($nextPaths) use ($match, $currentMethod, $unhandledPaths) {
				if (!count($nextPaths)) {
					return Observable::empty();
				}

				// We have to return an Observable of error instead of just
				// throwing.
				$matchedResults = null;
				try {
					$matchedResults = $match($currentMethod, $nextPaths);
				}  catch (Exception $error) {
					//print_r($error);
					//return Observable.throw(e);
					return Observable::empty();
				}

				// When there is explicitly not a match then we need to handle
				// the unhandled paths.
				if (!count($matchedResults)) {
					$unhandledPaths[] = $nextPaths;
					return Observable::empty();
				}

				echo "nn";
				//print_r($nextPaths);
			}
		);
		return $source;
		

	}

	/*public function get($paths) {
		$methodSummary = null;
		$jsongCache = (object) array();
		$action = $this->runGetAction($jsongCache, $methodSummary);
		$normPS = $this->normalizePathSets($paths);
		print_r($action);

		if ($this->getPathsCount($normPS) > $this->maxPaths) {
			//throw new MaxPathsExceededError();
		}

		$a = $this->recurseMatchAndExecute(
			$this->matcher, 
			$action, 
			$normPS,
			'get',
			$this, 
			$jsongCache
		)->flatMap(function ($i) {
			print_r($i);
		})->subscribe(function ($v) {
        echo $v . PHP_EOL;
    });

		print_r($a);


		
        return print_r($paths);
    }*/




}