<?php

namespace Peter;



//var Tokenizer = require('./tokenizer');
//var head = require('./parse-tree/head');
//var RoutedTokens = require('./RoutedTokens');

/*define("RoutedTokens", (object) [ 
    'integers' => 'integers',
    'ranges' => 'ranges',
    'keys' => 'keys' ]);*/

// Potential routed tokens.
class Parser {
    public static $RoutedTokens = RoutedTokens;

    public static function pathSyntax($string, $extendedRules = false) {
        return \Peter\head(new Tokenizer($string, $extendedRules));
    }

    // If the argument is a string, this with convert, else just return
    // the path provided.
    public static function fromPath($path, $ext =  false) {
        if (!$path) {
            return array();
        }

        if (getType($path) === 'string') {
            return self::pathSyntax($path, $ext);
        }

        return $path;
    }

    // Constructs the paths from paths / pathValues that have strings.
    // If it does not have a string, just moves the value into the return
    // results.
    public static function fromPathsOrPathValues($paths, $ext = false) {
        if (!$paths) {
            return array();
        }

        $out = array();
        for ($i = 0, $len = count($paths); $i < $len; $i++) {
            
            // Is the path a string
            if (getType($paths[$i]) === 'string') {
                $out[$i] = self::pathSyntax($paths[$i], $ext);
            }
            // is the path a path value with a string value.
            else if (getType($paths[$i]->path) === 'string') {
                
                $out[$i] = (object) [
                    'path' => self::pathSyntax($paths[$i]->path, $ext), 
                    'value' => $paths[$i]->value
                ];
            }

            // just copy it over.
            else {
                $out[] = $paths[$i];
            }
        }

        return $out;
    }

}