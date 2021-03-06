<?php

/**
 * @license MIT
 * @copyright 2016 Tim Gunter
 */

namespace Alice\Daemon;

/**
 * Daemon error exception
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package alice-common
 */
class ErrorException extends \ErrorException {

    protected $_context;

    public function __construct($message, $errorNumber, $file, $line, $context, $backtrace) {
        parent::__construct($message, $errorNumber, 0, $file, $line, null);
        $this->_context = $context;
    }

    public function getContext() {
        return $this->_context;
    }

}