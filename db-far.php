#!/usr/bin/php
<?php
/**
 * PHP script that perform a find and replace in a database dump (tested with
 * MySQL) with adjustments of the PHP serialize founded.
 *
 * Usage:
 *   $ db-far [options] [search] [replace] [file]
 *
 * Dependancy:
 *   The UNIX CLI command "sed" (http://www.gnu.org/software/sed/).
 *
 * Example:
 *   $ db-far --backup-ext=".old" "http://old.domain.ext" "http://new.domain.ext" backup-dumps/dump.sql
 */

// Options.
$options = array(
    'backup-ext' => array(
        'help'  => 'The extension of the backup file that will be produce.',
        'type'  => 'string',
        'value' => '.bak',
    ),
    'encoding' => array(
        'help'  => 'Encoding with which length of string are calculated for PHP serialize conversion. You can find the complete list at this URL: http://www.php.net/manual/en/mbstring.supported-encodings.php',
        'type'  => 'string',
        'value' => 'UTF-8',
    ),
    'preview' => array(
        'help'  => 'Like "verbose" but without executing the replacement.',
        'type'  => 'boolean',
        'value' => false,
    ),
    'verbose' => array(
        'help'  => 'Show the different options and arguments.',
        'type'  => 'boolean',
        'value' => false,
    ),
);

// Return the value of an option formatted to be print.
function format_option_value($option) {
    switch ($option['type']) {
        case 'boolean':
            return ($option['value']?'true':'false');
            break;
        default:
            if (strstr($option['value'], ' ') === false) {
                return $option['value'];
            }
            else{
                return '"'.$option['value'].'"';
            }
            break;
    }
}

// "echo" in Terminal and add "new line" when 80 caracters is reach.
function e($txt = '', $indentation = 0) {
    $max_length = 80;
    $indentation = $indentation*4;
    while ((mb_strlen($txt, 'UTF-8')+$indentation) > $max_length) {
        $line = substr($txt, 0, $max_length);
        $pos_space = strrpos($line, ' ');
        if ($pos_space === false) {
            $pos_space = $max_length;
        }
        echo str_repeat(' ', $indentation).trim(substr($line, 0, $pos_space)).PHP_EOL;
        $txt = substr($txt, $pos_space+1);
    }
    if (mb_strlen(trim($txt), 'UTF-8') > 0) {
        echo str_repeat(' ', $indentation).trim($txt).PHP_EOL;
    }
}

function show_help() {
    global $options;
    e('Usage');
    e('db-far [options] [search] [replace] [file]', 1);
    e();
    e("Options");
    foreach ($options as $key => $option) {
        e("--".$key." (".$option['type']."), default: --".$key."=".format_option_value($option), 1);
        e($option['help'], 2);
    }
}

// Delete the first argument (the command).
array_shift($argv);
// Arguments (contain raw options + arguments at this time).
$arguments = $argv;
// For each argument found in the command.
for ($k=0;$k<count($argv);$k++) {
    // If the command arg is an option.
    if (preg_match('/^--([^=]+)=(.*)$/', $argv[$k], $matches)) {
        // If the option is not valid.
        if (!array_key_exists($matches[1], $options)) {
            die('Invalid option: "'.$matches[1].'".');
        }
        else{
            // Override the option.
            switch ($options[$matches[1]]['type']) {
                case 'boolean':
                    $options[$matches[1]]['value'] = (strtolower($matches[2]) == 'true');
                    break;
                default:
                    $options[$matches[1]]['value'] = $matches[2];
                    break;
            }
            // Delete this "option" entry from the "arguments" array.
            array_shift($arguments);
        }
    }
    // No more options, the rest are arguments.
    else{
        break;
    }
}

// Check if encoding is supported.
$supported_encodings = mb_list_encodings();
if (!in_array($options['encoding']['value'], $supported_encodings)) {
    die('The encoding is not supported. See this page: http://www.php.net/manual/en/mbstring.supported-encodings.php');
}

// If the count of arguments is incorrect.
if (count($arguments) != 3) {
    show_help();exit;
}

// Arguments.
$search     = $arguments[0];
$replace    = $arguments[1];
$file       = $arguments[2];

function sanitize_sed_rx($str) {
    $str = sanitize_sed_replace($str);
    $str = str_replace('[', '\[', $str);
    $str = str_replace(']', '\]', $str);
    $str = str_replace('(', '\(', $str);
    $str = str_replace(')', '\)', $str);
    $str = str_replace('{', '\{', $str);
    $str = str_replace('}', '\}', $str);
    $str = str_replace('+', '\+', $str);
    $str = str_replace('?', '\?', $str);
    $str = str_replace('$', '\$', $str);
    $str = str_replace('^', '\^', $str);
    $str = str_replace('*', '\*', $str);
    $str = str_replace('.', '\.', $str);
    return $str;
}
function sanitize_sed_replace($str) {
    $str = str_replace('\\', '\\\\', $str);
    $str = str_replace('&', '\&', $str);
    $str = str_replace('/', '\/', $str);
    $str = str_replace('"', '\\\"', $str);
    $str = str_replace("'", "\\\'\''", $str);
    return $str;
}

// If option "preview" is set to "false".
if ($options['preview']['value'] === false) {
    // Database
    shell_exec("sed -i '".$options['backup-ext']['value']."' 's/".sanitize_sed_rx($search)."/".sanitize_sed_replace($replace)."/g' ".$file);
    // Correcting of lenght of string in PHP serialized
    $new_dump_sql = preg_replace_callback('/(s:)([0-9]*)(:\\")([^"]*'.str_replace('/', '\/', preg_quote($replace)).'[^"]*)(\\")/', function ($m){
        global $options;
        return($m[1].mb_strlen($m[4], $options['encoding']['value']).$m[3].$m[4].$m[5]);
    }, file_get_contents($file));
    file_put_contents($file, $new_dump_sql);
}

// If we have to show verbose.
if ($options['preview']['value'] || $options['verbose']['value']) {
    e("Options");
    foreach ($options as $key => $option) {
        e(str_pad($key, 12, ' ')."= ".format_option_value($option), 1);
    }
    e("Arguments");
    e(str_pad("search", 12, ' ')."= ".$search, 1);
    e(str_pad("replace", 12, ' ')."= ".$replace, 1);
    e(str_pad("file", 12, ' ')."= ".$file, 1);
}
?>