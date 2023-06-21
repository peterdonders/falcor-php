<?php
 
 namespace Peter;

/*var TokenTypes = require('./../TokenTypes');
var RoutedTokens = require('./../RoutedTokens');
var E = require('./../exceptions');
var routedE = E.routed;*/

define("RoutedTokens", (object) [ 
    'integers' => 'integers',
    'ranges' => 'ranges',
    'keys' => 'keys' ]);

/**
 * The routing logic.
 *
 * parse-tree:
 * <opening-brace><routed-token>(:<token>)<closing-brace>
 */
function routed($tokenizer, $openingToken, $state, $out) {
    $routeToken = $tokenizer->next();
    $named = false;
    $name = '';

    // ensure the routed token is a valid ident.
    switch ($routeToken->token) {
        case RoutedTokens->integers:
        case RoutedTokens->ranges:
        case RoutedTokens->keys:
            //valid
            break;
        default:
            throw new \Exception('Invalid routed token.  only integers|ranges|keys are supported.');
            //E.throwError(routedE.invalid, tokenizer);
            break;
    }

    // Now its time for colon or ending brace.
    $next = $tokenizer->next();

    // we are parsing a named identifier.
    if ($next->type === ':') {
        $named = true;

        // Get the token name or a white space character.
        $next = $tokenizer->next();

        // Skip over preceeding white space
        while ($next->type === ' ') {
            $next = $tokenizer->next();
        }

        if ($next->type !== 'token') {
            throw new \Exception('Invalid routed token.  only integers|ranges|keys are supported.');
            //E.throwError(routedE.invalid, tokenizer);
        }
        $name = $next->token;

        // Move to the closing brace or white space character
        $next = $tokenizer->next();

        // Skip over any white space to get to the closing brace
        while ($next->type === ' ') {
            $next = $tokenizer->next();
        }
    }

    // must close with a brace.

    if ($next->type === '}') {
        $outputToken = (object) [
            'type' => $routeToken->token,
            'named'=> $named,
            'name'=> $name
        ];

        $state->indexer[] = $outputToken;
    }

    // closing brace expected
    else {
        throw new \Exception('Invalid routed token.  only integers|ranges|keys are supported.');
        //E.throwError(routedE.invalid, tokenizer);
    }

}

