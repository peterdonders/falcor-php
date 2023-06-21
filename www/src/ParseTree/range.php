<?php

namespace Peter;

/**
 * The indexer is all the logic that happens in between
 * the '[', opening bracket, and ']' closing bracket.
 */
function customrange($tokenizer, $openingToken, $state, $out) {
    $token = $tokenizer->peek();
    $dotCount = 1;
    $done = false;
    $inclusive = true;

    // Grab the last token off the stack.  Must be an integer.
    $idx = count($state->indexer) - 1;
    $from = Tokenizer::toNumber($state->indexer[$idx]);
    $to;

   
    if (!is_numeric($from)) {
        throw new Exception('ranges must be preceded by numbers.');
        //E.throwError(E.range.precedingNaN, tokenizer);
    }

    // Why is number checking so difficult in javascript.

    while (!$done && !$token->done) {

        switch ($token->type) {

            // dotSeparators at the top level have no meaning
            case '.':
                if ($dotCount === 3) {
                    throw new Exception('Unexpected token.');
                    //E.throwError(E.unexpectedToken, tokenizer);
                }
                ++$dotCount;

                if ($dotCount === 3) {
                    $inclusive = false;
                }
                break;

            case 'token':
                // move the tokenizer forward and save to.
                $to = Tokenizer::toNumber($tokenizer->next()->token);

                // throw potential error.
                if (!is_numeric($to)) {
                    throw new Exception('ranges must be suceeded by numbers.');
                    //E.throwError(E.range.suceedingNaN, tokenizer);
                }

                $done = true;
                break;

            default:
                $done = true;
                break;
        }

        // Keep cycling through the tokenizer.  But ranges have to peek
        // before they go to the next token since there is no 'terminating'
        // character.
        if (!$done) {
            $tokenizer->next();

            // go to the next token without consuming.
            $token = $tokenizer->peek();
        }

        // break and remove state information.
        else {
            break;
        }
    }

    $state->indexer[$idx] = (object) [ 'from' => $from, 'to' => $inclusive ? $to : $to - 1 ];
}

