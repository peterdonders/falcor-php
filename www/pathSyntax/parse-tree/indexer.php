<?php

/*var TokenTypes = require('./../TokenTypes');
var E = require('./../exceptions');
var idxE = E.indexer;
var range = require('./range');
var quote = require('./quote');
var routed = require('./routed');*/

include ("range.php"); //range
include("quote.php");

/**
 * The indexer is all the logic that happens in between
 * the '[', opening bracket, and ']' closing bracket.
 */
function indexer($tokenizer, $openingToken, $state, $out) {
    $token = $tokenizer->next();
    $done = false;
    $allowedMaxLength = 1;
    $routedIndexer = false;

    // State variables
    $state->indexer = array();

    while (!$token->done) {
       

        switch ($token->type) {
            case "token":
            case "quote":

                if (count($state->indexer) === $allowedMaxLength) {
					throw new Exception('Indexers require commas between indexer args.');
                    //E.throwError(idxE.requiresComma, tokenizer);
                }
                break;
        }

        switch ($token->type) {
            // Extended syntax case
            case "{":
                $routedIndexer = true;
                routed($tokenizer, $token, $state, $out);
                break;


            case "token":
               
                if ($token->token != "") {
                    $t = +$token->token;
                }
                else {
                    $t = false;
                }
                
                if (!is_numeric($t)) {
                    throw new Exception("unquoted indexers must be numeric. -- ". json_encode($token));
                }
                $state->indexer[] = $t;
                break;

            // dotSeparators at the top level have no meaning
            case '.':
                if (!count($state->indexer)) {
                    throw new Exception("Indexers cannot have leading dots. -- ". json_encode($token));
                }
                customrange($tokenizer, $token, $state, $out);
                break;

            // Spaces do nothing.
            case ' ':
                break;

            case ']':
                $done = true;
                break;


            // The quotes require their own tree due to what can be in it.
            case 'quote':
                quote($tokenizer, $token, $state, $out);
                break;


            // Its time to decend the parse tree.
            case '[':
                throw new Exception("Indexers cannot be nested. -- ". json_encode($token));
                
                break;

            case ',':
                ++$allowedMaxLength;
                break;

            default:
                throw new Exception("Unexpected token. -- ". json_encode($token));
                break;
        }

        // If done, leave loop
        if ($done) {
            break;
        }

        // Keep cycling through the tokenizer.
        $token = $tokenizer->next();
    }

    

    if (count($state->indexer) === 0) {
        throw new Exception("cannot have empty indexers. -- ". json_encode($token));
    }

    if (count($state->indexer) > 1 && $routedIndexer) {
        throw new Exception("Only one token can be used per indexer when specifying routed tokens. -- ". json_encode($token));
    }

    // Remember, if an array of 1, keySets will be generated.
    if (count($state->indexer) === 1) {
        $state->indexer = $state->indexer[0];
    }

   

    $out[] = $state->indexer;

    

    // Clean state.
    //$state->indexer = null;

    return  $out;
};

