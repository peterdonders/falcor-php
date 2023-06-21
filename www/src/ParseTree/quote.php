<?php

namespace Peter;

/**
 * quote is all the parse tree in between quotes.  This includes the only
 * escaping logic.
 *
 * parse-tree:
 * <opening-quote>(.|(<escape><opening-quote>))*<opening-quote>
 */
function quote($tokenizer, $openingToken, $state, $out) {
    $token = $tokenizer->next();
    $innerToken = '';
    $openingQuote = $openingToken->token;
    $escaping = false;
    $done = false;

    while (!$token->done) {

        switch ($token->type) {
            case 'token':
            case ' ':

            case '.':
            case ',':

            case '[':
            case ']':
            case '{':
            case '}':
                if ($escaping) {
                    //E.throwError(quoteE.illegalEscape, tokenizer);
                    throw new Exception('Invalid escape character.  Only quotes are escapable.');
                }

                $innerToken .= $token->token;
                break;


            case 'quote':
                // the simple case.  We are escaping
                if ($escaping) {
                    $innerToken .= $token->token;
                    $escaping = false;
                }

                // its not a quote that is the opening quote
                else if ($token->token !== $openingQuote) {
                    $innerToken .= $token->token;
                }

                // last thing left.  Its a quote that is the opening quote
                // therefore we must produce the inner token of the indexer.
                else {
                    $done = true;
                }

                break;
            case '\\':
                $escaping = true;
                break;

            default:
                //E.throwError(E.unexpectedToken, tokenizer);
                throw new Exception('Unexpected token.');
        }

        // If done, leave loop
        if ($done) {
            break;
        }

        // Keep cycling through the tokenizer.
        $token = $tokenizer->next();
    }

    
    if ($innerToken === NULL) {
        throw new Exception('cannot have empty quoted keys.');
        //E.throwError(quoteE.empty, tokenizer);
    }

    $state->indexer[] = $innerToken;
}
