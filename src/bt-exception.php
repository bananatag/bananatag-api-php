<?php
/**
 * Bananatag BTException
 *
 * @author Bananatag Systems <eric@bananatag.com>
 * @version 1.0.0
 */

abstract class BTException extends Exception
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return 'BTException' . ": {$this->code}: {$this->message}\n";
    }
}