<?php 

use Rx\Observable;
use Rx\Scheduler;
use Rx\Scheduler\ImmediateScheduler;
function getExecutableMatches($matches, $pathSet) {

    

	$remainingPaths = $pathSet;
    $matchAndPaths = [];
    $out = [
        "matchAndPaths" => $matchAndPaths,
        "unhandledPaths" => false
	];

	for ($i = 0; $i < count($matches) && count($remainingPaths) > 0; ++$i) {
        $availablePaths = $remainingPaths;
        $match = $matches[$i];
        $currentMatch = $match;

		$remainingPaths = [];

        if ($i > 0) {
            $availablePaths = collapse($availablePaths);
        }

        // For every available path attempt to intersect.  If there
        // is an intersection then strip and replace.
        // any relative complements, add to remainingPaths
        for ($j = 0; $j < count($availablePaths); ++$j) {
            $path = $availablePaths[$j];
            if (hasIntersection($path, $match['virtual'])) {
                $stripResults = stripPath($path, $match['virtual']);
                $matchAndPaths[] = [
                    "path"=> $stripResults[0],
                    "match" => $currentMatch
				];
                
				$remainingPaths = array_merge($remainingPaths, $stripResults[1]);
            }
			else if ($i < count($matches) - 1) {
                $remainingPaths[count($remainingPaths)] = $path;
            }
        }
    }

    $out = [
        "matchAndPaths" => $matchAndPaths,
        "unhandledPaths" => false
	];

    // Adds the remaining paths to the unhandled paths section.
    if ($remainingPaths && count($remainingPaths)) {
        $out['unhandledPaths'] = $remainingPaths;
    }

   

    return $out;
}



function runByPrecedence($pathSet, $matches, $actionRunner) {
	
    
    // Precedence matching
	// Assuming $matches is an array of objects or associative arrays,
	// where each element has a 'precedence' property/key.
	usort($matches, function($a, $b) {
    	if ($a['precedence'] < $b['precedence']) {
        	return 1;
    	}
		elseif ($a['precedence'] > $b['precedence']) {
        	return -1;
    	}
    	return 0;
	});
    echo "ffff";

	$sortedMatches = $matches;

	$execs = getExecutableMatches($sortedMatches, [$pathSet]);
    $actions = array();
    foreach($execs['matchAndPaths'] as $value) {
       
        $actionTuple = $actionRunner($value);
        

        $actions['match'] = $actionTuple['match'];
        $actions['value'] =  $actionTuple['value'];
    }

   return $actions;
  

/*
    $setOfMatchedPaths = Observable::fromArray($execs['matchAndPaths'])
        //->merge($actionRunner);
        ->map(function($value) use ($actionRunner) {
            return $actionRunner($value);
        })
        // Note: We do not wait for each observable to finish,
        // but repeat the cycle per onNext.
        ->map(function($actionTuple) {
            return [
                'match'=> $actionTuple['path'],
                'value'=> $actionTuple['value']
            ];
        })

        ;

       //return $setOfMatchedPaths;

    $setOfMatchedPaths->subscribe(
        function ($x) {
            echo 'Nexta: ', json_encode($x), PHP_EOL;
        },
        function ($ex) {
            echo 'Error: ', $ex->getMessage(), " in: ", $ex->getFile(), " on Line: ", $ex->getLine(),  PHP_EOL;
        },
        function () {
            echo 'Completed', PHP_EOL;
        }
    );
*/

}