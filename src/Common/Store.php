<?php

/**
 * @license MIT
 * @copyright 2016 Tim Gunter
 */

namespace Alice\Common;

/**
 * Centralized data mart
 *
 * Allows data persist even through reloads.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package alice-common
 */
class Store {

    /**
     * Data store
     * @var array
     */
    public $data;

    public function __construct() {
        $this->data = [];
    }

    /**
     * Write data to store
     *
     * @param array $data
     */
    public function prepare($data) {
        $this->data = $data;
    }

    /**
     * Get data from store
     *
     * @return array
     */
    public function dump() {
        return $this->data;
    }

    /**
     * Do we have data?
     * @return boolean
     */
    public function hasData() {
        return count($this->data);
    }

    /**
     * Get a store variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return valr($key, $this->data, $default);
    }

    /**
     * Set a store value
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value) {
        setvalr($key, $this->data, $value);
        return $value;
    }

    /**
     * Unset a key
     *
     * @param string $key
     * @return boolean unset success or failure
     */
    public function delete($key) {
        $key = trim($key);
        $path = explode('.', $key);
        $pathLength = count($path);
        $target = &$this->config;
        for ($i = 1; $i <= $pathLength; ++$i) {
            $subKey = $path[$i - 1];

            // no such key!
            if (!isset($target[$subKey]) || ($i < $pathLength && !is_array($target[$subKey]))) {
                return;
            }

            if ($i < $pathLength) {
                $target = &$target[$subKey];
            }
        }
        unset($target[$subKey]);
    }

    /**
     * Push data onto key
     *
     * @param string $key
     * @param array $data
     */
    public function push($key, $data) {
        if (!array_key_exists($key, $this->data) || !is_array($this->data[$key])) {
            $this->data[$key] = [];
        }
        array_push($this->data[$key], $data);
    }

}
