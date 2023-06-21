<?php



$tokenTypes = (object) [
    'token' => 'token',
    'dotSeparator' => '.',
    'commaSeparator' => ',',
    'openingBracket' => '[',
    'closingBracket' => ']',
    'openingBrace' => '{',
    'closingBrace' => '}',
    'escape' => '\\',
    'space' => ' ',
    'colon' => ':',
    'quote' => 'quote',
    'unknown' => 'unknown'
];




define('DOT_SEPARATOR', '.');
define('COMMA_SEPARATOR', ',');
define('OPENING_BRACKET', '[');
define('CLOSING_BRACKET', ']');
define('OPENING_BRACE', '{');
define('CLOSING_BRACE', '}');
define('COLON', ':');
define('ESCAPE', '\\');
define('DOUBLE_OUOTES', '"');
define('SINGE_OUOTES', "'");
define('TAB', "\t");
define('SPACE', " ");
define('LINE_FEED', '\n');
define('CARRIAGE_RETURN', '\r');
//define('SPECIAL_CHARACTERS', '\\\'"[].,');
//define('EXT_SPECIAL_CHARACTERS', '\\{}\'"[]., :\t\n\r');

define('SPECIAL_CHARACTERS', '~(\'|\"|\[|\]|\.|\,| |\t|\n|\r)~');
define('EXT_SPECIAL_CHARACTERS', '~({|}|\'|\"|\[|\]|\.|\,| |\:|\t|\n|\r)~');

class Tokenizer {

    private $string;
    private $idx;
    private $extended;
    public $parseString = '';

    private $nextToken = false;

    public function __construct($string, $ext) {
        $this->string = $string;
        $this->idx = -1;
        $this->extended = $ext;
        $this->parseString = '';
    }


    /**
     * grabs the next token either from the peek operation or generates the
     * next token.
     */
    public function next() {

        if ($this->nextToken){
			$nextToken = $this->nextToken;
		}
		else {
			$nextToken = getNext($this->string, $this->idx, $this->extended);
		}

        
       
        $this->idx = $nextToken->idx;
        $this->nextToken = false;

        if (isset($nextToken->token->token)) {
        	$this->parseString .= $nextToken->token->token;
		}

        return $nextToken->token;
    }

    /**
     * will peak but not increment the tokenizer
     */
    public function peek() {
        $nextToken = $this->nextToken ? $this->nextToken : getNext($this->string, $this->idx, $this->extended);
        $this->nextToken = $nextToken;

        return $nextToken->token;
    }

    public static function toNumber($x) {
        
        if (!is_numeric(+$x)) {
            return +$x;
        }
        
        return (int) $x;
    }


}




function toOutput($token, $type, $done) {

    return (object) [
        'token' => $token,
        'done' => $done,
        'type' => $type
    ];
}

function getNext($string, $idx, $ext) {
   
    $output = false;
    $token = '';
    $specialChars = $ext ? EXT_SPECIAL_CHARACTERS : SPECIAL_CHARACTERS;
    $done = false;
   
    do {
        
        $done = $idx + 1 >= strlen($string);
        
        if ($done) {
            break;
        }

        // we have to peek at the next token
        $character = $string[$idx + 1];
        
       
        if ($character !== 'undefined' && 
        preg_match($specialChars, $character) == false) {
        //if (isset($character) && strpos($specialChars, $character) === false) {

            $token .= $character;
            ++$idx;
            
            continue;
        }

        // The token to delimiting character transition.
        else if (strlen($token)) {
           
           break;
        }



       

        ++$idx;
        switch ($character) {
            case DOT_SEPARATOR:
                $type = '.';
                break;
            case COMMA_SEPARATOR:
                $type = ',';
                break;
            case OPENING_BRACKET:
                $type = '[';
                break;
            case CLOSING_BRACKET:
                $type = ']';
                break;
            case OPENING_BRACE:
                $type = '{';
                break;
            case CLOSING_BRACE:
                $type = '}';
                break;
            case TAB:
            case SPACE:
            case LINE_FEED:
            case CARRIAGE_RETURN:
                $type = ' ';
                break;
            case DOUBLE_OUOTES:
            case SINGE_OUOTES:
                $type = 'quote';
                break;
            case ESCAPE:
                $type = '\\';
                break;
            case COLON:
                $type = ':';
                break;
            default:
                $type = 'unknown';
                break;
        }
        
        $output = toOutput($character, $type, false);
       
        break;
    } while (!$done);

    

    if (!$output && strlen($token)) {
        
        $output = toOutput($token, 'token', false);
    }
    

    if (!$output) {
        $output = (object) [
            'done' => true,
        ];
    }

    return (object) [
        'token' => $output,
        'idx'=>  $idx
    ];
}


