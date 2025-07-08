<?php

// Simulatie van Falcor-gerelateerde constanten en functies
class Keys {
    const ranges = 'ranges';
    const integers = 'integers';
    const keys = 'keys';
    const match = '__match'; // Simulatie van de Falcor interne match sleutel
    const named = 'named';
    const name = 'name';
}

class Precedence {
    const ranges = 1;
    const integers = 2;
    const keys = 3;
    const specific = 4;
}

function cloneArray(array $arr): array {
    return $arr; // PHP arrays zijn standaard copy-on-write voor eenvoudige datatypes
}

function specificMatcher($keySet, $curr): array {
    // Implementeer de logica van specifieke matching hier
    // Dit is een placeholder
    $matchedKeys = [];
    if (is_array($keySet)) {
        foreach ($keySet as $key) {
            if (isset($curr[$key])) {
                $matchedKeys[] = $key;
            }
        }
    } elseif (is_string($keySet) || is_numeric($keySet)) {
        if (isset($curr[$keySet])) {
            $matchedKeys[] = $keySet;
        }
    }
    return $matchedKeys;
}

function pluckIntegers($keySet): array {
    // Implementeer de logica om integers uit de keySet te halen
    // Dit is een placeholder
    $integers = [];
    if (is_array($keySet)) {
        foreach ($keySet as $key) {
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                $integers[] = (int)$key;
            }
        }
    }
    return $integers;
}

function isRoutedToken($token): bool {
    // Implementeer de logica om te controleren of een token een routed token is
    // Dit is een placeholder
    return is_array($token) && isset($token['type']);
}

class CallNotFoundError extends Exception {
    public $throwToNext = false;
    public function __construct($message = "Call not found", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

// Functie die `pathUtils.collapse` nabootst. Dit is een complexe functie die
// een Falcor-implementatie vereist. Hier is een simplistische benadering.
function collapse(array $paths): array {
    // Dit is een zeer simplistische simulatie. Een echte Falcor collapse
    // zou veel complexer zijn en rekening houden met ranges, keySets, etc.
    // Voor dit voorbeeld retourneren we de paden zoals ze zijn, of proberen we
    // eenvoudige reeksen te identificeren.
    $collapsedPaths = [];
    foreach ($paths as $path) {
        $collapsedPaths[] = $path;
    }
    return $collapsedPaths;
}

$intTypes = [
    ['type' => Keys::ranges, 'precedence' => Precedence::ranges],
    ['type' => Keys::integers, 'precedence' => Precedence::integers]
];
$keyTypes = [
    ['type' => Keys::keys, 'precedence' => Precedence::keys]
];
$allTypes = array_merge($intTypes, $keyTypes);

$get = 'get';
$set = 'set';
$call = 'call';


/**
 * Maakt een aangepaste matching functie voor de match boom.
 * @param array $rst De 'routed syntax tree'.
 * @return Closure
 */
function matcher(array $rst): Closure {
    
	$get = 'get';
$set = 'set';
$call = 'call';
	
	/**
     * Dit is waar de matching wordt gedaan. Zal recursief de paden matchen
     * totdat alle matchbare functies zijn gevonden.
     * @param string $method
     * @param array $paths
     */
    return function (string $method, array $paths) use ($rst, $get, $set, $call) {
        $matched = [];
        $missing = [];
        localMatch($rst, $paths, $method, $matched, $missing);
		

        // We zijn aan het einde van het pad, maar er is geen match en het is een oproep.
        // Daarom gooien we een informatieve fout.
        if ($method === $call && empty($matched)) {
            $err = new CallNotFoundError();
            $err->throwToNext = true;
            throw $err;
        }

        // Reduceer meerdere gematchte routes in route sets waar elke match
        // hetzelfde route-eindpunt matcht. Vanaf hier kunnen we de gematchte
        // paden reduceren tot de meest optimale pathSet met collapse.
        $reducedMatched = array_reduce($matched, function (array $acc, array $matchedRoute) {
            $id = $matchedRoute['id'];
            if (!isset($acc[$id])) {
                $acc[$id] = [];
            }
            $acc[$id][] = $matchedRoute;
            return $acc;
        }, []);

        $collapsedMatched = [];

        // Voor elke set gematchte routes, collapse en reduceer de gematchte set
        // tot het minimale aantal 'collapsed sets'.
        foreach ($reducedMatched as $k => $reducedMatch) {
            // Als de 'reduced match' een lengte van één heeft, is er geen
            // behoefte aan 'collapsing', aangezien er niets te 'collapsen' is.
            if (count($reducedMatch) === 1) {
                $collapsedMatched[] = $reducedMatch[0];
                continue;
            }

            // Aangezien er meer dan 1 route is, moeten we kijken of
            // ze kunnen 'collapsen' en het aantal arrays kunnen wijzigen.
            $collapsedResults = collapse(
                array_map(function ($x) {
                    return $x['requested'];
                }, $reducedMatch)
            );

            // Voor elk 'collapsed resultaat' gebruiken we het eerder gematchte resultaat
            // en werken we het 'requested' en 'virtual' pad bij. Voeg dan die
            // match toe aan de 'collapsedMatched' set.
            foreach ($collapsedResults as $i => $path) {
                // Let op: De originele JS-code heeft hier een potentiële mismatch
                // tussen 'path' en 'reducedMatch[i]'. Als `collapse` het aantal
                // resultaten verandert, werkt `reducedMatch[i]` niet meer.
                // We gaan ervan uit dat `collapse` de volgorde en het aantal behoudt
                // voor deze gesimuleerde vertaling.
                if (!isset($reducedMatch[$i])) {
                    // Dit zou een indicatie zijn dat collapse de structuur heeft gewijzigd.
                    // Voor een Falcor-implementatie zou dit nader onderzoek vereisen.
                    continue;
                }
                $collapsedMatch = $reducedMatch[$i];
                $reducedVirtualPath = $collapsedMatch['virtual'];

                foreach ($path as $index => $atom) {
                    // Als het geen routed token is, vervang dan de hele atom.
                    if (!isRoutedToken($reducedVirtualPath[$index] ?? null)) {
                        $reducedVirtualPath[$index] = $atom;
                    }
                }
                $collapsedMatch['requested'] = $path;
                $collapsedMatch['virtual'] = $reducedVirtualPath; // Update virtual path
                $collapsedMatched[] = $collapsedMatch;
            }
        }
        return $collapsedMatched;
    };
}

/**
 * Matcht paden recursief.
 * @param array $curr De huidige knoop in de RST.
 * @param array $path Het resterende pad om te matchen.
 * @param string $method De methode (get, set, call).
 * @param array $matchedFunctions Referentie naar de array van gematchte functies.
 * @param array $missingPaths Referentie naar de array van ontbrekende paden.
 * @param int $depth De huidige diepte van de recursie.
 * @param array $requested Het 'requested' pad tot nu toe.
 * @param array $virtual Het 'virtual' pad tot nu toe.
 * @param array $precedence De 'precedence' tot nu toe.
 * @return void
 */
function localMatch(
    array $curr, array $path, string $method, array &$matchedFunctions,
    array &$missingPaths, int $depth = 0, array $requested = [],
    array $virtual = [], array $precedence = []
) {
    global $get, $set, $call, $intTypes, $keyTypes, $allTypes;

    // Niets meer te matchen
    if (empty($curr)) {
        return;
    }

    // Aan het einde van het pad zijn we een matchende functie tegengekomen.
    // Tijd om te beëindigen.
    // Get: eenvoudige methode matching
    // Set/Call: De methode is uniek. Als het pad niet compleet is,
    // wat betekent dat de diepte equivalent is aan de lengte,
    // dan matchen we een 'get'-methode, anders matchen we een 'set' of 'call'-methode.
    $atEndOfPath = count($path) === $depth;
    $isSet = $method === $set;
    $isCall = $method === $call;
    $methodToUse = $method;

    if (($isCall || $isSet) && !$atEndOfPath) {
        $methodToUse = $get;
    }

    // Slaat het gematchte resultaat op als het gevonden is langs of aan het einde van
    // het pad. Als we een set doen en er is geen set-handler
    // maar er is een get-handler, dan moeten we de get-handler gebruiken.
    // Dit is zodat de huidige waarde die in de cache van de client zit
    // niet wordt gematerialiseerd.
    $currentMatch = $curr[Keys::match] ?? null;

    // Van https://github.com/Netflix/falcor-router/issues/76
    // Set: Als er geen set-handler is, moeten we standaard de get-handler uitvoeren
    // zodat we de lokale waarden van de client niet vernietigen.
    if ($currentMatch && $isSet && !isset($currentMatch[$set])) {
        $methodToUse = $get;
    }

    // Controleer of we een match hebben
    if ($currentMatch && isset($currentMatch[$methodToUse])) {
        $matchedFunctions[] = [
            // Gebruikt voor het 'collapsen' van paden die routes met meerdere
            // string indexers gebruiken.
            'id' => $currentMatch[$methodToUse . 'Id'] ?? uniqid(), // Fallback voor id
            'requested' => cloneArray($requested),
            'prettyRoute' => $currentMatch['prettyRoute'] ?? null,
            'action' => $currentMatch[$methodToUse],
            'authorize' => $currentMatch['authorize'] ?? null,
            'virtual' => cloneArray($virtual),
            'precedence' => (int)implode('', $precedence),
            'suffix' => array_slice($path, $depth),
            'isSet' => $atEndOfPath && $isSet,
            'isCall' => $atEndOfPath && $isCall
        ];
    }

    // Als de diepte het einde heeft bereikt, moeten we stoppen met recursie. Dit
    // kan vreemde neveneffecten veroorzaken bij het matchen tegen {keys} als het laatste
    // argument wanneer een pad is uitgeput (undefined is nog steeds een sleutelwaarde).
    //
    // Voorbeeld:
    // route1: [{keys}]
    // route2: [{keys}][{keys}]
    //
    // pad: ['('].
    //
    // Dit zal route1 en 2 matchen omdat we niet afbreken op lengte en er
    // een {keys} matcher is die "undefined" waarde zal matchen.
    if ($depth === count($path)) {
        return;
    }

    $keySet = $path[$depth] ?? null;
    $next = null;

    // -------------------------------------------
    // Specifieke sleutel-matcher.
    // -------------------------------------------
    $specificKeys = specificMatcher($keySet, $curr);
    foreach ($specificKeys as $key) {
        $virtual[$depth] = $key;
        $requested[$depth] = $key;
        $precedence[$depth] = Precedence::specific;

        // Tijd om te recursen
        localMatch(
            $curr[$key],
            $path, $method, $matchedFunctions,
            $missingPaths, $depth + 1,
            $requested, $virtual, $precedence
        );

        // Verwijdert de virtuele, aangevraagde en precedentie-informatie
        array_splice($virtual, $depth);
        array_splice($requested, $depth);
        array_splice($precedence, $depth);
    }

    $ints = pluckIntegers($keySet);
    $keys = $keySet; // Kan een array of scalar zijn
    $intsLength = count($ints);

    // -------------------------------------------
    // ints, ranges en keys matcher.
    // -------------------------------------------
    $filteredTypes = array_filter($allTypes, function ($typeAndPrecedence) use ($curr, $intsLength) {
        $type = $typeAndPrecedence['type'];
        // één extra stap nodig voor int-types
        if ($type === Keys::integers || $type === Keys::ranges) {
            return isset($curr[$type]) && $intsLength > 0;
        }
        return isset($curr[$type]);
    });

    foreach ($filteredTypes as $typeAndPrecedence) {
        $type = $typeAndPrecedence['type'];
        $prec = $typeAndPrecedence['precedence'];
        $next = $curr[$type];

        $virtual[$depth] = [
            'type' => $type,
            'named' => $next[Keys::named] ?? null,
            'name' => $next[Keys::name] ?? null
        ];

        // De aangevraagde set informatie moet worden ingesteld als
        // integers, indien int-matchers, of keys
        if ($type === Keys::integers || $type === Keys::ranges) {
            $requested[$depth] = $ints;
        } else {
            $requested[$depth] = $keys;
        }

        $precedence[$depth] = $prec;

        // Vervolg het matching-algoritme.
        localMatch(
            $next,
            $path, $method, $matchedFunctions,
            $missingPaths, $depth + 1,
            $requested, $virtual, $precedence
        );

        // verwijdert de toegevoegde sleutels
        array_splice($virtual, $depth);
        array_splice($requested, $depth);
        array_splice($precedence, $depth);
    }
}