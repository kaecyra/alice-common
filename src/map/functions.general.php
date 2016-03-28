<?php

/**
 * General functions
 *
 * @copyright 2016 Tim Gunter
 * @license MIT
 * @package alice-common
 */

/**
 * Concatenate path elements into single string.
 *
 * Takes a variable number of arguments and concatenates them. Delimiters will
 * not be duplicated. Example: all of the following invocations will generate
 * the path "/path/to/vanilla/applications/dashboard"
 *
 * '/path/to/vanilla', 'applications/dashboard'
 * '/path/to/vanilla/', '/applications/dashboard'
 * '/path', 'to', 'vanilla', 'applications', 'dashboard'
 * '/path/', '/to/', '/vanilla/', '/applications/', '/dashboard'
 *
 * @return string Returns the concatenated path.
 */
function paths() {
    $paths = func_get_args();
    $delimiter = '/';
    if (is_array($paths)) {
        $mungedPath = implode($delimiter, $paths);
        $mungedPath = str_replace(
            array($delimiter.$delimiter.$delimiter, $delimiter.$delimiter),
            array($delimiter, $delimiter),
            $mungedPath
        );
        return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $mungedPath);
    } else {
        return $paths;
    }
}

/**
 * Return the value from an associative array or an object.
 *
 * @param string $key The key or property name of the value.
 * @param mixed $collection The array or object to search.
 * @param mixed $default The value to return if the key does not exist.
 * @return mixed The value from the array or object.
 */
function val($key, $collection, $default = false) {
    if (is_array($collection)) {
        if (array_key_exists($key, $collection)) {
            return $collection[$key];
        } else {
            return $default;
        }
    } elseif (is_object($collection) && property_exists($collection, $key)) {
        return $collection->$key;
    }
    return $default;
}

/**
 * Return the value from an associative array or an object.
 *
 * This function differs from GetValue() in that $Key can be a string consisting of dot notation that will be used
 * to recursively traverse the collection.
 *
 * @param string $key The key or property name of the value.
 * @param mixed $collection The array or object to search.
 * @param mixed $default The value to return if the key does not exist.
 * @return mixed The value from the array or object.
 */
function valr($key, $collection, $default = false) {
    $path = explode('.', $key);

    $value = $collection;
    for ($i = 0; $i < count($path); ++$i) {
        $subKey = $path[$i];

        if (is_array($value) && isset($value[$subKey])) {
            $value = $value[$subKey];
        } elseif (is_object($value) && isset($value->$subKey)) {
            $value = $value->$subKey;
        } else {
            return $default;
        }
    }
    return $value;
}

/**
 * Set a key to a value in a collection.
 *
 * Works with single keys or "dot" notation. If $key is an array, a simple
 * shallow array_merge is performed.
 *
 * @param string $key The key or property name of the value.
 * @param array &$collection The array or object to search.
 * @param mixed $value The value to set.
 * @return mixed Newly set value or if array merge.
 */
function setvalr($key, &$collection, $value = null) {
    if (is_array($key)) {
        $collection = array_merge($collection, $key);
        return null;
    }

    if (strpos($key, '.')) {
        $path = explode('.', $key);

        $selection = &$collection;
        $mx = count($path) - 1;
        for ($i = 0; $i <= $mx; ++$i) {
            $subSelector = $path[$i];

            if (is_array($selection)) {
                if (!isset($selection[$subSelector])) {
                    $selection[$subSelector] = array();
                }
                $selection = &$selection[$subSelector];
            } elseif (is_object($selection)) {
                if (!isset($selection->$subSelector)) {
                    $selection->$subSelector = new stdClass();
                }
                $selection = &$selection->$subSelector;
            } else {
                return null;
            }
        }
        return $selection = $value;
    } else {
        if (is_array($collection)) {
            return $collection[$key] = $value;
        } else {
            return $collection->$key = $value;
        }
    }
}

/**
 * Set a key to a value in a collection.
 *
 * Works with single keys or "dot" notation. If $key is an array, a simple
 * shallow array_merge is performed.
 *
 * @param string $key The key or property name of the value.
 * @param array &$collection The array or object to search.
 * @param mixed $value The value to set.
 * @return mixed Newly set value or if array merge
 * @deprecated Use {@link setvalr()}.
 */
function svalr($key, &$collection, $value = null) {
    setvalr($key, $collection, $value);
}

/**
 * Return the plural version of a word depending on a number.
 *
 * This can be overridden in language definition files like:
 *
 * ```
 * /applications/garden/locale/en-US/definitions.php.
 * ```
 */
function plural($number, $singular, $plural, $formattedNumber = false) {
    // Make sure to fix comma-formatted numbers
    $workingNumber = str_replace(',', '', $number);
    if ($formattedNumber === false) {
        $formattedNumber = $number;
    }

    $format = abs($workingNumber) == 1 ? $singular : $plural;
    return sprintf($format, $formattedNumber);
}

/**
 * Generate a random string of characters with additional character options that can be cryptographically strong.
 *
 * This function attempts to use {@link openssl_random_pseudo_bytes()} to generate its randomness.
 * If that function does not exists then it just uses mt_rand().
 *
 * @param int $length The length of the string.
 * @param string $characterOptions Character sets that are allowed in the string. This is a string made up of the following characters.
 *  - A: uppercase characters
 *  - a: lowercase characters
 *  - 0: digits
 *  - !: basic punctuation (~!@#$^&*_+-)
 * @return string Returns the random string for the given arguments.
 */
function betterRandomString($length, $characterOptions = 'A0') {
    $characterClasses = array(
        'A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'a' => 'abcdefghijklmnopqrstuvwxyz',
        '0' => '0123456789',
        '!' => '~!@#$^&*_+-'
    );

    $characters = '';
    for ($i = 0; $i < strlen($characterOptions); $i++) {
        $characters .= val($characterOptions{$i}, $characterClasses);
    }

    $charLen = strlen($characters);
    $string = '';

    if (function_exists('openssl_random_pseudo_bytes')) {
        $random_chars = unpack('C*', openssl_random_pseudo_bytes($length));
        foreach ($random_chars as $c) {
            $offset = (int) $c % $charLen;
            $string .= substr($characters, $offset, 1);
        }
    } else {
        for ($i = 0; $i < $length; ++$i) {
            $offset = mt_rand() % $charLen;
            $string .= substr($characters, $offset, 1);
        }
    }
    return $string;
}

/**
 * Formats a string by inserting data from its arguments, similar to sprintf, but with a richer syntax.
 *
 * @param string $string The string to format with fields from its args enclosed in curly braces. The format of fields is in the form {Field,Format,Arg1,Arg2}. The following formats are the following:
 *  - date: Formats the value as a date. Valid arguments are short, medium, long.
 *  - number: Formats the value as a number. Valid arguments are currency, integer, percent.
 *  - time: Formats the valud as a time. This format has no additional arguments.
 *  - url: Calls Url() function around the value to show a valid url with the site. You can pass a domain to include the domain.
 *  - urlencode, rawurlencode: Calls urlencode/rawurlencode respectively.
 *  - html: Calls htmlspecialchars.
 * @param array $args The array of arguments. If you want to nest arrays then the keys to the nested values can be seperated by dots.
 * @return string The formatted string.
 * <code>
 * echo formatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
 * // This would output the following string:
 * // Hello Frank, It's 12:59PM.
 * </code>
 */
function formatString($string, $args = array()) {
    formatStringCallback($args, true);
    $result = preg_replace_callback('/{([^\s][^}]+[^\s]?)}/', 'formatStringCallback', $string);

    return $result;
}

function formatStringCallback($match, $setArgs = false) {
    static $args = array();
    if ($setArgs) {
        $args = $match;
        return;
    }

    $match = $match[1];
    if ($match == '{') {
        return $match;
    }

    // Parse out the field and format.
    $parts = explode(',', $match);
    $field = trim($parts[0]);
    $format = strtolower(trim(val(1, $parts, '')));
    $subFormat = strtolower(trim(val(2, $parts, '')));
    $formatArgs = val(3, $parts, '');

    if (in_array($format, array('currency', 'integer', 'percent'))) {
        $formatArgs = $subFormat;
        $subFormat = $format;
        $format = 'number';
    } elseif (is_numeric($subFormat)) {
        $formatArgs = $subFormat;
        $subFormat = '';
    }

    $value = valr($field, $args, '');
    if ($value == '' && $format != 'url') {
        $result = '';
    } else {
        switch (strtolower($format)) {
            case 'date':
                $timeValue = strtotime($value);
                switch ($subFormat) {
                    case 'short':
                        $result = date($timeValue, 'd/m/Y');
                        break;
                    case 'medium':
                        $result = date($timeValue, 'j M Y');
                        break;
                    case 'long':
                        $result = date($timeValue, 'j F Y');
                        break;
                    default:
                        $result = date($timeValue);
                        break;
                }
                break;
            case 'html':
            case 'htmlspecialchars':
                $result = htmlspecialchars($value);
                break;
            case 'number':
                if (!is_numeric($value)) {
                    $result = $value;
                } else {
                    switch ($subFormat) {
                        case 'currency':
                            $result = '$' . number_format($value, is_numeric($formatArgs) ? $formatArgs : 2);
                        case 'integer':
                            $result = (string) round($value);
                            if (is_numeric($formatArgs) && strlen($result) < $formatArgs) {
                                $result = str_repeat('0', $formatArgs - strlen($result)) . $result;
                            }
                            break;
                        case 'percent':
                            $result = round($value * 100, is_numeric($formatArgs) ? $formatArgs : 0);
                            break;
                        default:
                            $result = number_format($value, is_numeric($formatArgs) ? $formatArgs : 0);
                            break;
                    }
                }
                break;
            case 'rawurlencode':
                $result = rawurlencode($value);
                break;
            case 'time':
                $timeValue = strtotime($value);
                $result = date($timeValue, 'H:ia');
                break;
            case 'url':
                if (strpos($field, '/') !== false) {
                    $value = $field;
                }
                $result = Url($value, $subFormat == 'domain');
                break;
            case 'urlencode':
                $result = urlencode($value);
                break;
            default:
                $result = $value;
                break;
        }
    }
    return $result;
}

/** Checks whether or not string $haystack begins with string $needle.
 *
 * @param string $haystack The main string to check.
 * @param string $needle The substring to check against.
 * @param bool $caseInsensitive Whether or not the comparison should be case insensitive.
 * @param bool $trim Whether or not to trim $needle off of $haystack if it is found.
 * @return bool|string Returns true/false unless $trim is true.
 */
function stringBeginsWith($haystack, $needle, $caseInsensitive = false, $trim = false) {
    if (strlen($haystack) < strlen($needle)) {
        return $trim ? $haystack : false;
    } elseif (strlen($needle) == 0) {
        if ($trim) {
            return $haystack;
        }
        return true;
    } else {
        $result = substr_compare($haystack, $needle, 0, strlen($needle), $caseInsensitive) == 0;
        if ($trim) {
            $result = $result ? substr($haystack, strlen($needle)) : $haystack;
        }
        return $result;
    }
}

/** Checks whether or not string $haystack ends with string needle.
 *
 * @param string $haystack The main string to check.
 * @param string $needle The substring to check against.
 * @param bool $caseInsensitive Whether or not the comparison should be case insensitive.
 * @param bool $trim Whether or not to trim $need off of $haystack if it is found.
 * @return bool|string Returns true/false unless $trim is true.
 */
function stringEndsWith($haystack, $needle, $caseInsensitive = false, $trim = false) {
    if (strlen($haystack) < strlen($needle)) {
        return $trim ? $haystack : false;
    } elseif (strlen($needle) == 0) {
        if ($trim) {
            return $haystack;
        }
        return true;
    } else {
        $result = substr_compare($haystack, $needle, -strlen($needle), strlen($needle), $caseInsensitive) == 0;
        if ($trim) {
            $result = $result ? substr($haystack, 0, -strlen($needle)) : $haystack;
        }
        return $result;
    }
}

/**
 * Remove a file or folder
 *
 * @param string $path
 * @return void
 */
function rm($path) {
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path)) {
        unlink($path);
        return;
    }

    $path = rtrim($path, '/') . '/';

    // Get all of the files in the directory.
    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (trim($file, '.') == '') {
                continue;
            }

            $subPath = $path . $file;

            if (is_dir($subPath)) {
                rm($subPath);
            } else {
                unlink($subPath);
            }
        }
        closedir($dh);
    }
    rmdir($path);
}

/**
 * Write file atomically
 *
 * @param string $filename
 * @param string $content
 * @param octal $mode
 * @return boolean
 */
function file_put_contents_atomic($filename, $content, $mode = 0644) {
    $temp = tempnam(dirname($filename), 'atomic');

    if (!($fp = @fopen($temp, 'wb'))) {
        $temp = dirname($filename) . '/' . uniqid('atomic');
        if (!($fp = @fopen($temp, 'wb'))) {
            trigger_error("file_put_contents_atomic() : error writing temporary file '{$temp}'", E_USER_WARNING);
            return false;
        }
    }

    $br = fwrite($fp, $content);
    fclose($fp);
    if (!$br || $br != strlen($content)) {
        unlink($temp);
        return false;
    }

    chmod($temp, $mode);

    if (!rename($temp, $filename)) {
        unlink($filename);
        rename($temp, $filename);
    }
    return true;
}

/**
 * Calculate the sha1 checksum of a folder
 *
 * @internal recursive
 * @param string $folder path to folder
 */
function sha1_dir($folder) {
    if (!file_exists($folder)) {
        return false;
    }
    if (!is_dir($folder)) {
        return sha1_file($folder);
    }

    $hashes = [];
    $entries = scandir($folder);
    $skip = ['.', '..'];
    foreach ($entries as $path) {
        if (in_array($path, $skip)) {
            continue;
        }
        $full = paths($folder, $path);
        if (is_dir($full)) {
            $hashes[$full] = sha1_dir($full);
        } else {
            $hashes[$full] = sha1_file($full);
        }
    }

    ksort($hashes);
    return sha1(serialize($hashes));
}

function lock($lockfile) {
    if (locked($lockfile)) {
        return false;
    }

    $myPid = getmypid();
    file_put_contents($lockfile, $myPid);
    return true;
}

function unlock($lockfile) {
    @unlink($lockfile);
    return true;
}

/**
 * Check if this lockFile corresponds to a locked process
 *
 * @param string $lockFile
 * @param boolean $recover
 * @return boolean
 */
function locked($lockFile, $recover = true) {
    $myPid = getmypid();
    if (!file_exists($lockFile)) {
        return false;
    }

    $lockPid = trim(file_get_contents($lockFile));

    // This is my lockfile, nothing to do
    if ($myPid == $lockPid) {
        return false;
    }

    // Is the PID running?
    posix_kill($lockPid, 0);
    $psExists = !(bool)posix_get_last_error();

    // No? Unlock and return Locked=false
    if (!$psExists && $recover) {
        unlock($lockFile);
        return false;
    }

    // Someone else is already running
    return true;
}

/**
 * Check if a pid is running
 *
 * @param string $pidFile
 * @return boolean
 */
function running($pidFile) {
    if (!file_exists($pidFile)) {
        return false;
    }

    $runPid = trim(file_get_contents($pidFile));
    if (!$runPid) {
        return false;
    }

    // Is the PID running?
    $running = posix_kill($runPid, 0);
    if (!$running) {
        return false;
    }

    // Did we have trouble pinging that PID?
    $psExists = !(bool)posix_get_last_error();

    return $psExists;
}

/**
 * Get agent pid
 *
 * @param string $pidFile
 * @return integer|false
 */
function getPid($pidFile) {
    if (!file_exists($pidFile)) {
        return false;
    }

    $runPid = trim(file_get_contents($pidFile));
    if (!$runPid) {
        return false;
    }

    // Is the PID running?
    $running = posix_kill($runPid, 0);
    if (!$running) {
        return false;
    }

    // Did we have trouble pinging that PID?
    $psExists = !(bool)posix_get_last_error();

    return $psExists ? $runPid : false;
}