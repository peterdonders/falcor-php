<?php

namespace Peter\PathSyntax;

include("tokenizer.php");
include("parse-tree/head.php");

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
        return head(new Tokenizer($string, $extendedRules));
    }
}

function pathSyntax($string, $extendedRules = false) {
    //$a = new Tokenizer($string, $extendedRules);
    //print_r($a);
   // $h = head($a);
    return head(new Tokenizer($string, $extendedRules));
    //return head(new Tokenizer(string, extendedRules));
};



/*// Constructs the paths from paths / pathValues that have strings.
// If it does not have a string, just moves the value into the return
// results.
parser.fromPathsOrPathValues = function(paths, ext) {
    if (!paths) {
        return [];
    }

    var out = [];
    for (var i = 0, len = paths.length; i < len; i++) {

        // Is the path a string
        if (typeof paths[i] === 'string') {
            out[i] = parser(paths[i], ext);
        }

        // is the path a path value with a string value.
        else if (typeof paths[i].path === 'string') {
            out[i] = {
                path: parser(paths[i].path, ext), value: paths[i].value
            };
        }

        // just copy it over.
        else {
            out[i] = paths[i];
        }
    }

    return out;
};

// If the argument is a string, this with convert, else just return
// the path provided.
parser.fromPath = function(path, ext) {
    if (!path) {
        return [];
    }

    if (typeof path === 'string') {
        return parser(path, ext);
    }

    return path;
};

// Potential routed tokens.
parser.RoutedTokens = RoutedTokens;
*/