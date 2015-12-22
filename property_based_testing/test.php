<?php

// This file assumes that php-quickcheck is installed in the
// current directory using composer.

require 'vendor/autoload.php';

use QCheck\Generator;
use QCheck\Quick;

$predicates = array(
    'is array' => function($result) {
        return is_array($result);
    },
    'same as join' => function($result, $original, $glue) {
        return is_array($result) ? join($glue, $result) === $original : false;
    },
    'is split' => function($result, $original, $glue) {
        $count = strlen($original) - strlen(str_replace($glue, '', $original));
        return count($result) == $count + 1;
    },
);

function check($function, array &$predicates) {
    $results = array();
    foreach($predicates as $name => $p) {
        echo "$name : ";
        $results[$name] = Quick::check(
            500,
            Generator::forAll(
                [Generator::asciiStrings(), Generator::asciiChars()],
                function($string, $separator) use($function, $p) {
                    $exploded = $function($separator, $string);
                    return $p($exploded, $string, $separator);
                }),
            ['echo' => true]
        );

        if($results[$name]['result']) echo "\n";
    }

    echo "\n\n";
    foreach($results as $name => $result) {
        if($result['result'] !== true) {
            echo "$name : \nFailing input\n";
            echo "\tstring :\t"; var_export($result['fail'][0]); echo "\n";
            echo "\tseparator :\t"; var_export($result['fail'][1]); echo "\n";
            echo "Smallest failing input\n";
            echo "\tstring :\t"; var_export($result['shrunk']['smallest'][0]); echo "\n";
            echo "\tseparator :\t"; var_export($result['shrunk']['smallest'][1]); echo "\n";
        }

        echo "\n";
    }
}

function split1($sep, $s) {
    $result = array();
    $current = "";

    $length = strlen($s);
    for($i = 0; $i < $length; ++$i) {
        $c = $s[$i];

        if($c == $sep) {
            $result[] = $current;
        } else {
            $current .= $c;
        }
    }
    return $result;
}

function split2($sep, $s) {
    $result = array();
    $current = "";

    $length = strlen($s);
    for($i = 0; $i < $length; ++$i) {
        $c = $s[$i];

        if($c == $sep) {
            $result[] = $current;
            $current = '';
        } else {
            $current .= $c;
        }
    }
    return $result;
}

function split3($sep, $s) {
    $result = array();
    $current = "";

    $length = strlen($s);
    for($i = 0; $i < $length; ++$i) {
        $c = $s[$i];

        if($c == $sep) {
            $result[] = $current;
            $current = '';
        } else {
            $current .= $c;
        }

        if($i == $length - 1) {
            $result[] = $current;
        }
    }
    return $result;
}


function split4($sep, $s) {
    $result = array();
    $current = "";

    $length = strlen($s);
    for($i = 0; $i < $length; ++$i) {
        $c = $s[$i];

        if($c == $sep) {
            $result[] = $current;
            $current = '';
        } else {
            $current .= $c;
        }
    }
    $result[] = $current;
    return $result;
}

var_dump(split3('a', 'a '));

// check('split2', $predicates);
