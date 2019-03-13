<?php

# defining the variables
$xml = new XMLWriter();                             # creation of the xml document
$file = 'NIL';                                      # name of the file to put the stats in
$options = 'NIL';
$order = 0;                                         # order of the instruction in the input code
$comments = 0;                                      # number of comments in the input code
$labels = 0;                                        # number of unique labels in the input code
$jumps = 0;                                         # number of of jumps in the input code
$array = array();                                   # array that stores the lines from input
$instructions = array(                              # array storing the list of predefined instructions found in IPP-code19
    "MOVE" => array("var", "symb"),
    "CREATEFRAME" => array(),
    "PUSHFRAME" => array(),
    "POPFRAME" => array(),
    "DEFVAR" => array("var"),
    "CALL" => array("label"),
    "RETURN" => array(),
    "PUSHS" => array("symb"),
    "POPS" => array("var"),
    "ADD" => array("var", "symb", "symb"),
    "SUB" => array("var", "symb", "symb"),
    "MUL" => array("var", "symb", "symb"),
    "IDIV" => array("var", "symb", "symb"),
    "LT" => array("var", "symb", "symb"),
    "GT" => array("var", "symb", "symb"),
    "EQ" => array("var", "symb", "symb"),
    "AND" => array("var", "symb", "symb"),
    "OR" => array("var", "symb", "symb"),
    "NOT" => array("var", "symb"),
    "INT2CHAR" => array("var", "symb"),
    "STRI2INT" => array("var", "symb", "symb"),
    "READ" => array("var", "type"),
    "WRITE" => array("symb"),
    "CONCAT" => array("var", "symb", "symb"),
    "STRLEN" => array("var", "symb"),
    "GETCHAR" => array("var", "symb", "symb"),
    "SETCHAR" => array("var", "symb", "symb"),
    "TYPE" => array("var", "symb"),
    "LABEL" => array("label"),
    "JUMP" => array("label"),
    "JUMPIFEQ" => array("label", "symb", "symb"),
    "JUMPIFNEQ" => array("label", "symb", "symb"),
    "EXIT" => array("symb"),
    "DPRINT" => array("symb"),
    "BREAK" => array()
);

# calling the function to handle the arguments of the script
handle_arguments($argv);

# calling the function to store the input lines into an array
$array = store_lines();

# setting the default start of the xml document
if ($xml->openUri('php://output') == FALSE) {
    //fwrite(STDERR, "Error while opening the file for xml output!");
    exit(12);
}
$xml->startDocument('1.0', 'UTF-8');
$xml->setIndent(true);
$xml->startElement('program');
$xml->startAttribute('language');
$xml->text('IPPcode19');
$xml->endAttribute();

# calling the function to check the lexical and syntactic correctness of the input
check_lines();

if ($file !== 'NIL') {
    write_stats($options);
}

# closing the xml document
$xml->endElement();
$xml->endDocument();

/**
 * Function checks whether the script was called with any arguments
 *
 * @param $arguments array The arguments that the script is called with
 */
function handle_arguments($arguments) {
    global $file;
    global $options;

    $shortopts = "";
    $longopts = array(
        "help",
        "loc",
        "labels",
        "jumps",
        "comments",
        "stats:"
    );

    $options = getopt($shortopts, $longopts);

    if (array_key_exists('help', $options)) {
        echo "This is a script that analyses the IPP-code19 code.\n";
        echo "It checks the for lexical and syntactic errors.\n";
        echo "The program is written in PHP 7.3.\n";
        echo "It accepts arguments these arguments:\n";
        echo "--help (shows this message), --stats=file (writes statistics to user chosen file), --loc (writes number of lines to file),\n";
        echo "--comments (writes number of lines with comments to file), --labels (writes number of labels to file) and\n";
        echo "--jumps (writes number of jumps to file).\n";
        exit(0);
    }
    if (array_key_exists('stats', $options)) {
        $file = $options['stats'];
        #write_stats($options);
    }
    else {
        if (array_key_exists('loc', $options)) {
            //fwrite(STDERR, "Argument 'loc' cannot be used without the argument 'stats=file'!\n");
            exit(10);
        }
        elseif (array_key_exists('labels', $options)) {
            //fwrite(STDERR, "Argument 'comments' cannot be used without the argument 'stats=file'!\n");
            exit(10);
        }
        elseif (array_key_exists('comments', $options)) {
            //fwrite(STDERR, "Argument 'labels' cannot be used without the argument 'stats=file'!\n");
            exit(10);
        }
        elseif (array_key_exists('jumps', $options)) {
            //fwrite(STDERR, "Argument 'jumps' cannot be used without the argument 'stats=file'!\n");
            exit(10);
        }
    }
}

/**
 * Function stores every line from stdin into an array
 *
 * @return array returns the created array
 */
function store_lines() {
    global $array;

    //$text = fopen("text.txt", "r");
    $text = fopen('php://stdin', 'r');
    if ($text == FALSE) {
        //fwrite(STDERR, "Error while opening the input!");
        exit(11);
    }

    while ($line = fgets($text)) {
        $array[] = trim($line);
    }

    return $array;
}


/**
 * Function checks whether the lines of the input code are written correctly
 */
function check_lines()
{
    global $array;
    global $instructions;
    global $order;
    global $comments;
    global $jumps;
    global $labels;

    if ((strpos(strtoupper($array[0]), '.IPPCODE19')) === FALSE) {
        //fwrite(STDERR, "Code does not start with the correct expression .IPPcode19!");
        exit(21);
    }

    unset($array[0]);
    $array = array_values($array);

    foreach ($array as $line) {
        $element = explode(" ", $line);

        # deleting all the comments
        if (!empty(preg_grep("/^#.*$/", $element))) {
            $length = count($element);
            $comments++;
            $i = preg_grep("/^#.*$/", $element);
            foreach($i as $key => $value) {
                if ($value !== null) {
                    break;
                }
            }
            for ($i = $key; $i < $length; $i++) {
                unset($element[$i]);
            }
        }

        foreach ($element as $key => $item) {
            if (preg_match("/^\s*$/", $item)) {
                unset($element[$key]);
            }
        }

        $element= array_values($element);

        if (count($element) === 0) {
            continue;
        }
        elseif (count($element) === 1 && $element[0] === "\n") {
            continue;
        }
        elseif ((array_key_exists(trim(strtoupper($element[0])), $instructions)) === FALSE) {
            //fwrite(STDERR, "Instruction does not exist!");
            exit(22);
        }
        else {
            $element[0] = strtoupper($element[0]);
            if (empty($instructions[$element[0]])) {
                if (count($element) !== 1) {
                    //fwrite(STDERR, "Wrong number of arguments after an instruction!");
                    exit(23);
                }
                else {
                    $order++;
                    generate_xml(trim($element[0]), 'NIL');
                }
            } else {
                if ((count($element) - 1) !== count($instructions[$element[0]])) {
                    //fwrite(STDERR, "Wrong number of arguments after an instruction!");
                    exit(23);
                }
                else {
                    for ($i = 0; $i < count($instructions[$element[0]]); $i++) {
                        if (check_data($element[$i + 1], $instructions[$element[0]][$i]) === FALSE) {
                            //fwrite(STDERR, "Incorrect data type used with an instruction!");
                            exit(23);
                        }
                    }
                    if ((strpos($element[0], 'JUMP')) !== FALSE) {
                        $jumps++;
                    }
                    if ((strpos($element[0], 'LABEL')) !== FALSE) {
                        $labels++;
                    }
                    $order++;
                    generate_xml($element[0], $element);
                }
            }
        }
    }
}

/**
 * Function checks whether the data types of an instruction are used correctly
 *
 * @param $element string One string from the line that has to be checked
 * @param $exp_type string The expected data type
 */
function check_data($element, $exp_type) {
    switch ($exp_type) {
        case "var":
            if (preg_match('/^([G|L|T]F@)-{0,1}[\p{L}|_|-|$|&|%|*|!|?][\p{L}|_|-|$|&|%|*|!|?]+$/u', trim($element))) {
                return true;
            }
            else {
                return false;
            }
            break;
        case "symb":
            if (preg_match('/^(bool@(true|false|TRUE|FALSE))$/', trim($element))) {
                return true;
            }
            elseif (preg_match('/^([G|L|T]F@)[\p{L}|_|-|$|&|%|*|!|?][\p{L}|_|-|$|&|%|*|!|?]+$/u', trim($element))) {
                return true;
            }
            elseif (preg_match('/^(nil@nil)$/', trim($element))) {
                return true;
            }
            elseif (preg_match('/^(int@[+|-]?\d+)$/', trim($element))) {
                return true;
            }
            if (preg_match('/^(int@)-{0,1}[\p{L}|_|-|$|&|%|*|!|?][\p{L}|_|-|$|&|%|*|!|?]+$/u', trim($element))) {
                return true;
            }
            elseif (preg_match('/^string@([^\x{000}-\x{020}\x{023}\x{05c}]|[\\\\][0-9]{3})*$/u', trim($element))) {
                return true;
            }
            else {
                return false;
            }
            break;
        case "label":
            if (preg_match('/([\p{L}]*(\\\\[0-9]{3})*)*/u', trim($element))) {
                return true;
            }
            else {
                return false;
            }
            break;
        case "type":
            if (preg_match('/^(string|int|bool)$/', trim($element))) {
                return true;
            }
            else {
                return false;
            }
            break;
        default:
            return false;
            break;
    }
}

/**
 * Function creates and fills an xml file with information about the input code
 *
 * @param $opcode string The name of the current instruction
 * @param $element array The array of strings (attributes) of the current instruction
 */
function generate_xml($opcode, $element) {
    global $xml;
    global $order;
    global $instructions;

    $xml->startElement('instruction');
    $xml->startAttribute('order');
    $xml->text($order);
    $xml->endAttribute();
    $xml->startAttribute( 'opcode');
    $xml->text($opcode);
    $xml->endAttribute();

    for ($i = 1; $i < (count($instructions[$opcode]) + 1); $i++) {
        $xml->startElement('arg' . $i);
        $xml->startAttribute('type');
        if ($instructions[$opcode][$i - 1] === "var") {
            $xml->text('var');
            $xml->endAttribute();
            $xml->text(trim(($element[$i])));
        }
        elseif ($instructions[$opcode][$i - 1] === "label") {
            $xml->text('label');
            $xml->endAttribute();
            $xml->text(trim(substr($element[$i], strpos($element[$i], '@'))));
        }
        elseif ($instructions[$opcode][$i - 1] === "type") {
            $xml->text('type');
            $xml->endAttribute();
            $xml->text(trim(substr($element[$i], (strpos($element[$i], '@') + 1))));
        }
        else {
            if (preg_match('/^([G|L|T]F@)[\p{L}|_|-|$|&|%|*|!|?][\p{L}|_|-|$|&|%|*|!|?]+$/u', trim($element[$i]))) {
                $xml->text('var');
                $xml->endAttribute();
                $xml->text(trim(($element[$i])));
            }
            elseif (preg_match('/^(int@[+|-]?\d+)$/', trim($element[$i]))) {
                $xml->text(substr($element[$i], 0, strpos($element[$i], '@')));
                $xml->endAttribute();
                $xml->text(trim(substr($element[$i], (strpos($element[$i], '@') + 1))));
            }
            elseif (preg_match('/^(nil@nil)$/', trim($element[$i]))) {
                $xml->text(substr($element[$i], 0, strpos($element[$i], '@')));
                $xml->endAttribute();
                $xml->text(trim(substr($element[$i], (strpos($element[$i], '@') + 1))));
            }
            elseif (preg_match('/^(bool@(true|false|TRUE|FALSE))$/', trim($element[$i]))) {
                $xml->text('bool');
                $xml->endAttribute();
                $xml->text(strtolower(trim(substr($element[$i], (strpos($element[$i], '@') + 1)))));
            }
            else {
                if (strpos($element[$i], "<")) {
                    $element[$i] = str_replace("<", '&lt;', $element[$i]);
                }
                elseif (strpos($element[$i], ">")) {
                    $element[$i] = str_replace("<", '&gt;', $element[$i]);
                }
                elseif (strpos($element[$i], "&")) {
                    $element[$i] = str_replace("<", '&amp;', $element[$i]);
                }
                $xml->text(substr($element[$i], 0, strpos($element[$i], '@')));
                $xml->endAttribute();
                $xml->text(trim(substr($element[$i], (strpos($element[$i], '@') + 1))));
            }
        }
        $xml->endElement();
    }
    $xml->endElement();
}

function write_stats($options) {
    global $comments;
    global $labels;
    global $order;
    global $jumps;
    global $file;

    file_put_contents($file, "");

    foreach ($options as $key => $option) {
        if (preg_match('/labels/', $key)) {
            file_put_contents($file, $labels . "\n", FILE_APPEND);
        }
        elseif (preg_match('/loc/', $key)) {
            file_put_contents($file, $order . "\n", FILE_APPEND);
        }
        elseif (preg_match('/jumps/', $key)) {
            file_put_contents($file, $jumps . "\n", FILE_APPEND);
        }
        elseif (preg_match('/comments/', $key)) {
            file_put_contents($file, $comments . "\n", FILE_APPEND);
        }
    }
}