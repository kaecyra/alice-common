<?php

/**
 * @license MIT
 * @copyright 2016 Tim Gunter
 */

namespace Alice\Common;

/**
 * Lightweight event framework
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package alice-common
 */
class Event {

    /**
     * List of hooks
     * @var array
     */
    protected static $hooks = [];

    /**
     * Whether we are ticking
     * @var boolean
     */
    protected static $ticking = false;

    /**
     * We tick every 10 seconds by default
     * @var integer
     */
    protected static $tickFreq = 10;
    protected static $lastTick = 0;

    /**
     * Fire an event
     *
     * @param string $event
     * @param array $arguments Optional.
     */
    public static function fire($event, $arguments = null) {
        $arguments = (array)$arguments;
        foreach (Event::hooks($event) as $callback) {
            if (is_callable($callback)) {
                call_user_func_array($callback, $arguments);
            }
        }
    }

    /**
     * Fire an event from a sender object
     *
     * @param string $event
     * @param object $sender
     * @param array $arguments optional.
     */
    public static function fireOff($event, $sender, $arguments = null) {
        $arguments = (array)$arguments;
        array_unshift($arguments, $sender);
        return Event::fire($event, $arguments);
    }

    /**
     * Fire events in sequence
     *
     * Each callback receives the return value of the preceding callback as its
     * first argument.
     *
     * EventType: Filter
     * @param string $event
     * @param mixed $filter
     * @param array $arguments optional.
     */
    public static function fireFilter($event, $filter, $arguments = null) {
        $arguments = (array)$arguments;

        array_unshift($arguments, $filter);
        $filter = &$arguments[0];

        foreach (Event::hooks($event) as $callback) {
            if (is_callable($callback)) {
                $arguments[0] = call_user_func_array($callback, $arguments);
            }
        }

        return $filter;
    }

    /**
     * Fire event and collect the return values in an array
     *
     * EventType: Return
     * @param string $event
     */
    public static function fireReturn($event) {
        $return = [];
        $arguments = func_get_args();
        array_shift($arguments);

        foreach (Event::hooks($event) as $callback) {
            if (is_callable($callback)) {
                $return[] = call_user_func_array($callback, $arguments);
            }
        }
        return $return;
    }

    /**
     *
     *
     * @param type $event
     * @param type $arguments
     */
    public static function fireReflected($event, $arguments = null) {
        foreach (Event::hooks($event) as $callback) {
            $pass = [];
            if (is_string($callback) && is_callable($callback)) {
                $reflect = new ReflectionFunction($callback);
                $pass = !is_null($arguments) ? Event::reflect($reflect, $arguments) : null;
                $reflect->invokeArgs($pass);
            } elseif (is_array($callback) && is_callable($callback)) {
                $reflect = new ReflectionMethod($callback[0], $callback[1]);
                $pass = !is_null($arguments) ? Event::reflect($reflect, $arguments) : null;
                $reflectOn = is_string($callback[0]) ? null : $callback[0];
                $reflect->invokeArgs($reflectOn, $pass);
            } else {
                continue;
            }
        }
    }

    /**
     * Register an event hook
     *
     * @param string $event
     * @param callable $callback
     * @return string|boolean:false
     */
    public static function hook($event, $callback) {
        // We can't hook to something that isn't callable
        if (!is_callable($callback)) {
            return false;
        }

        if (!isset(Event::$hooks[$event]) || !is_array(Event::$hooks[$event])) {
            Event::$hooks[$event] = [];
        }

        // Check if this event is already registered
        $signature = Event::hash($callback);
        if (array_key_exists($signature, Event::$hooks[$event])) {
            return $signature;
        }

        Event::$hooks[$event][$signature] = $callback;
        return $signature;
    }

    /**
     * Get all hooks for an event
     *
     * @param string $event
     * @return array
     */
    protected static function hooks($event) {
        if (!isset(Event::$hooks[$event]) || !is_array(Event::$hooks[$event])) {
            return [];
        }
        return Event::$hooks[$event];
    }

    /**
     * Remove an event hook
     *
     * @param string $event
     * @param string $signature
     * @return boolean successfully removed, or didn't exist
     */
    public static function unhook($event, $signature) {
        if (!isset(Event::$hooks[$event]) || !is_array(Event::$hooks[$event]) || !array_key_exists($signature, Event::$hooks[$event])) {
            return false;
        }

        unset(Event::$hooks[$event][$signature]);
        return true;
    }

    /**
     * Reflect on the object or function and format an ordered arguement list
     *
     * @param Reflector $reflect
     * @param array $arguments
     */
    protected static function reflect($reflect, $arguments) {
        $pass = [];
        foreach ($reflect->getParameters() as $param) {
            if (isset($arguments[$param->getName()])) {
                $pass[] = $arguments[$param->getName()];
            } else {
                $pass[] = $param->getDefaultValue();
            }
        }
        return $pass;
    }

    /**
     * Get unique hash / id for callables
     *
     * @param callable $callback
     * @return string
     */
    public static function hash($callback) {

        // Global function calls
        if (is_string($callback)) {
            return 'string:'.$callback;
        }

        // Standardize to array callables
        if (is_object($callback)) {
            $callback = [$callback, ''];
        } else {
            $callback = (array)$callback;
        }

        // Callback to an instance method
        if (is_object($callback[0])) {

            return spl_object_hash($callback[0]) .'->'. $callback[1];

        // Callback is static
        } elseif (is_string($callback[0])) {

            return $callback[0] .'::'. $callback[1];

        }
    }

    /**
     * Enable periodic tick event
     *
     * @param integer $tickFreq optional
     * @return boolean
     */
    public static function enableTicks($tickFreq = null) {

        if (is_null($tickFreq)) {
            $tickFreq = self::$tickFreq;
        }

        // Change ticking frequency
        self::$tickFreq = $tickFreq;
        self::$lastTick = microtime(true);

        // If we're already ticking, don't register again
        if (self::$ticking) {
            return true;
        }

        register_tick_function(['\Alice\Common\Event', 'tick']);
    }

    /**
     * Disable periodic tick event
     *
     */
    public static function disableTicks() {
        if (self::$ticking) {
            unregister_tick_function(['\Alice\Common\Event', 'tick']);
        }
    }

    /**
     * Fire tick events every self::$tickFreq
     *
     */
    public static function tick() {
        if ((microtime(true) - self::$lastTick) > self::$tickFreq) {
            self::$lastTick = microtime(true);
            Event::fire('tick');
        }
    }

}
