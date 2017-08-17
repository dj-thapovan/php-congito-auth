<?php

use Phalcon\Logger\Formatter\Line;

/**
 * Description of LogFormatter
 *
 * @author panchapakesanvaidyanathan1
 */
class LogFormatter extends \Phalcon\Logger\Formatter\Line {

    public function format($message, $type, $timestamp, $context) {

        $dateTime = new \DateTime;
        $dateTime->setTimestamp($timestamp);
        $dateFormat = str_replace('u', floor((microtime(true) - $timestamp) * 1000), $this->getDateFormat());

        $formated = str_replace('%date%', $dateTime->format($dateFormat), $this->getFormat());
        $formated = str_replace('%message%', $message, $formated);
        return str_replace('%type%', $this->getTypeString($type), $formated) . "\n";
    }

}
