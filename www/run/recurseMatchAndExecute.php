<?php

use Rx\Observable;



/*class Observable extends RxObservable 
{

	protected function _subscribe(Rx\ObserverInterface $observer): Rx\DisposableInterface {
		//
	}

	
   
    public static function expand($source, callable $expanderFunc): array
    {
		print_r($source);
        $allResults = [];
        $queue = $source;

        while (!empty($queue)) {
            $nextPaths = array_shift($queue);

            // De expanderFunc kan een array van nieuwe paden teruggeven of een lege array/null.
            $expandedItems = call_user_func($expanderFunc, $nextPaths);

            if (!is_array($expandedItems)) {
                // Als de expanderFunc 'Observable::empty()' simuleert, sla dan over
                if (is_null($expandedItems)) {
                    continue;
                }
                // Als de expanderFunc 'Observable::throw()' simuleert, gooi dan de fout
                if ($expandedItems instanceof Throwable) {
                    throw $expandedItems;
                }
            }

            // Voeg de resultaten van de huidige expansie toe aan de totale resultaten
            $allResults = array_merge($allResults, $expandedItems);

            // Voeg de nieuw gegenereerde paden toe aan de wachtrij voor verdere expansie
            // Dit simuleert de recursieve aard van expand.
            foreach ($expandedItems as $item) {
                if (is_array($item) && !empty($item)) { // Voorzichtig, afhankelijk van wat expanderFunc retourneert
                    // Hier is een aanname dat de expanderFunc een array van arrays van paden kan retourneren
                    // of een array van resultaten die op hun beurt weer paden bevatten die verder moeten.
                    // In dit specifieke JS voorbeeld, 'pathsToExpand' wordt geretourneerd en is een array van paden.
                    $queue[] = $item;
                }
            }
        }
        return $allResults;
    }
}*/

// Dummy functies voor de benodigde 'requires'
// Deze moeten worden vervangen door je eigen implementaties
// of geïmporteerde bibliotheken als die bestaan in PHP.

/**
 * @param array $nextPaths
 * @param array $matchedResults
 * @param callable $actionRunner
 * @return array Gesimuleerde resultaten van de acties.
 */
function runByPrecedence(array $nextPaths, array $matchedResults, callable $actionRunner): array
{
    // Simuleer de logica van runByPrecedence
    // Voor dit voorbeeld retourneren we dummy data
    $results = [];
    foreach ($matchedResults as $match) {
        // Simulatie van de actie-uitvoering
        $value = call_user_func($actionRunner, $match); // Aanname dat actionRunner de match verwerkt
        $results[] = [
            'value' => $value,
            'match' => $match,
        ];
    }
    return $results;
}

/**
 * Simulatie van falcor-path-utils's collapse.
 * @param array $paths
 * @return array
 */
/*function collapse(array $paths): array
{
    // Simuleer de logica om paden samen te vouwen
    // Voor dit voorbeeld retourneren we de paden zoals ze zijn
    return $paths;
}*/

/**
 * Simulatie van optimizePathSets.
 * @param array $jsongCache
 * @param array $pathsToOptimize
 * @param int $maxRefFollow
 * @return array
 */
function optimizePathSets(array $jsongCache, array $pathsToOptimize, int $maxRefFollow): array
{
    // Simuleer de logica om paden te optimaliseren
    // Voor dit voorbeeld retourneren we de paden zoals ze zijn
    return $pathsToOptimize;
}

/**
 * Simulatie van mergeCacheAndGatherRefsAndInvalidations (mCGRI).
 * @param array $jsongCache
 * @param array $values
 * @param object $routerInstance
 * @return array Met 'invalidations', 'unhandledPaths', 'messages', 'references'.
 */
function mergeCacheAndGatherRefsAndInvalidations(array &$jsongCache, array $values, object $routerInstance): array
{
    // Dit is een complexe functie die de JsongCache muteert en verschillende resultaten retourneert.
    // Voor dit voorbeeld retourneren we dummy data.
    // In een echte implementatie zou dit de Falcor cache logica implementeren.

    $invalidations = [];
    $unhandledPaths = [];
    $messages = [];
    $references = [];

    foreach ($values as $value) {
        // Voorbeeld: als een waarde een 'ref' is, voeg deze toe aan referenties
        if (isset($value['$type']) && $value['$type'] === 'ref' && isset($value['value'])) {
            $references[] = ['value' => $value['value']]; // Aanname structuur
        }
        // Voorbeeld: als een waarde een fout is, voeg toe aan onverwerkte paden
        if (isset($value['$type']) && $value['$type'] === 'error' && isset($value['path'])) {
            $unhandledPaths[] = $value['path'];
        }
        // Voorbeeld: simulatie van invalidatie
        if (isset($value['$type']) && $value['$type'] === 'atom' && isset($value['invalidated']) && $value['invalidated'] === true) {
            $invalidations[] = ['path' => $value['path']];
        }

        // Simulatie van berichten die de stroom beïnvloeden
        if (isset($value['message'])) {
            $messages[] = $value['message'];
        }

        // Simpele cache update, dit is zeer vereenvoudigd!
        // In een Falcor router zou dit veel complexer zijn met paden en referenties
        // $jsongCache = array_merge($jsongCache, ['someKey' => 'someValue']);
    }

    return [
        'invalidations' => $invalidations,
        'unhandledPaths' => $unhandledPaths,
        'messages' => $messages,
        'references' => $references,
    ];
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
): array {
    $unhandledPaths = [];
    $invalidated = [];
    $reportedPaths = [];
    $currentMethod = $method;

	$source = Observable::fromArray($paths);

	$source
    ->flatMap(static fn (array $items) => Observable::fromArray($items))
    ->do(static fn (int $item) => print_r(sprintf('Item: %d', $item)))
    ->subscribe();

return [];

	/*foreach($paths as $nextPaths) {
		print_r($nextPaths);

		if (!count($nextPaths)) {
			
		}

		// We have to return an Observable of error instead of just
		// throwing.
		$matchedResults = null;
		try {
			$matchedResults = $match($currentMethod, $nextPaths);
		} catch (Exception $error) {
			return Exception($error);
		}

		// When there is explicitly not a match then we need to handle
		// the unhandled paths.
		if (!count($matchedResults)) {
			$unhandledPaths[] = $nextPaths;
			
		}


	}*/

    
    // map(function() { ... })
    // De map operator op het einde verzamelt de uiteindelijke staat van de verzamelde data.
    //return [
    //    'unhandledPaths' => $unhandledPaths,
    //    'invalidated' => $invalidated,
    //    'jsonGraph' => $jsongCache,
     //   'reportedPaths' => $reportedPaths,
    //];
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
): array {
    // We moeten een kopie van de cache doorgeven die dan door referentie wordt gemuteerd
    // in _recurseMatchAndExecute.
    $cacheCopy = $jsongCache;
    return _recurseMatchAndExecute(
        $match,
        $actionRunner,
        $paths,
        $method,
        $routerInstance,
        $cacheCopy
    );
}
