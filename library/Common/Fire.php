<?php

/**
 * @license MIT
 * @copyright 2016 Tim Gunter
 */

namespace Alice\Common;

/**
 * Wire-up for event firing with senders
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package alice
 */
class Fire {

    /**
     * Fire an event with a sender
     *
     * @param string $event
     * @param array $arguments Optional.
     */
    public function fire($event, $arguments = null) {
        return Event::fireOff($event, $this, $arguments);
    }

}
