<?php

include("parse-tree/parseTree.php");

$noOp = function() {};
$defaultNow = function() {
    return Date.now();
};

define ('MAX_REF_FOLLOW', 50);
define ('MAX_PATHS', 9000);

class Router {
    
    private $routes;
    private $rst;
    private $matcher;

    private $debug;
    private $pathErrorHook;
    private $errorHook;
    private $methodSummaryHook;
    private $now;

    public $maxRefFollow;
    public $maxPaths;


    public function __construct($routes, $options = stdClass) {
        $this->routes = $routes;
        $this->rst = parseTree($routes);
        //$this->matcher = matcher($this->rst);
        $this->setOptions($options);

    }

    private function setOptions($options) {
        
        $this->debug = $options->debug;
        $this->pathErrorHook = ($options->hooks && $options->hooks->pathError) || $noOp;
        $this->errorHook = $options->hooks && $options->hooks->error;
        $this->methodSummaryHook = $options->hooks && $options->hooks->methodSummary;
        $this->now = ($options->hooks && $options->hooks->now) || $options->now || $defaultNow;
        $this->maxRefFollow = $options->maxRefFollow || MAX_REF_FOLLOW;
        $this->maxPaths = $options->maxPaths || MAX_PATHS;
    }
    
   
}