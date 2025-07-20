<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

include("./vendor/autoload.php");

use React\EventLoop\Factory;
use Rx\Scheduler;

use Rx\Observable;
use Rx\ObserverInterface;
use Rx\SchedulerInterface;

use Rx\Operator\OperatorInterface;




$createStdoutObserver = function ($prefix = '') {
    return new Rx\Observer\CallbackObserver(
        function ($value) use ($prefix) { echo $prefix . "Next value: " . json_encode($value) . "\n"; },
        function ($error) use ($prefix) { echo $prefix . "Exception: " . $error->getMessage() . "\n"; },
        function ()       use ($prefix) { echo $prefix . "Complete!\n"; }
    );
};

$stdoutObserver = $createStdoutObserver();

function getmytest($a, $array, $b, $c) {


    $out = $a($array);

    return Rx\Observable::of($out);

    //return Rx\Observable::range($array, 3);
}

function myTest($a, $b, $c) {
   
    $source = Observable::of(function ($array) use ($a, $b, $c) {
       
        return getmytest($a, $array, $b, $c);
    });

    

    return $source;

}

$a = function(array $call) {
  
    return $call;
};


$loop = Factory::create();

//You only need to set the default scheduler once
Scheduler::setDefaultFactory(function() use($loop){
    return new Scheduler\EventLoopScheduler($loop);
});

$testing  = myTest($a, "b", "c");

$source = Observable::fromArray([['test', "test"]]);
$source->merge($testing);

$source->subscribe(
    function ($x) {
        echo 'Next: ', json_encode($x), PHP_EOL;
    },
    function (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), PHP_EOL;
    },
    function () {
        echo 'Completed', PHP_EOL;
    }
);

$loop->run();