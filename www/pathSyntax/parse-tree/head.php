<?php

include("indexer.php");
//var indexer = require('./indexer');

/**
 * The top level of the parse tree.  This returns the generated path
 * from the tokenizer.
 */
function head($tokenizer) {
    $token = $tokenizer->next();
    $state = (object) [];
    $out = array();

    while (!$token->done) {
        
        

        switch ($token->type) {
            case 'token':
                /*var_dump(+$token->token[0]);
                var_dump(is_numeric(+$token->token[0]));
                if (is_numeric($token->token[0])) {
                    echo "a";
                    
                }
                else {
                    echo "c";
                }

                if ($token->token != "") {
                    $first = +$token->token[0];
                }
                else {
                    $first = false;
                }
                
                if (!is_numeric($first)) {
                    throw new Exception("Invalid Identifier. -- ". json_encode($token));
                }*/
                $out[] = $token->token;
                
                break;

            // dotSeparators at the top level have no meaning
            case '.':
                if (count($out) === 0) {
                    throw new Exception("Unexpected token. -- ". json_encode($tokenizer));
                }
                break;

            // Spaces do nothing.
            case ' ':
                // NOTE: Spaces at the top level are allowed.
                // titlesById  .summary is a valid path.
                break;


            // Its time to decend the parse tree.
            case '[':
                $out = indexer($tokenizer, $token, $state, $out);
                break;

            default:
                throw new Exception("Unexpected token. -- ". json_encode($tokenizer));
                
                break;
        }

        // Keep cycling through the tokenizer.
        $token = $tokenizer->next();
    }

    if (count($out)=== 0) {
        throw new Exception('Please provide a valid path. -- '. json_encode($tokenizer));
        
    }

    return $out;
};

