<?php
namespace Spalenza\PromoHint\Logger;

use Monolog\Logger as MonologLogger;

/**
 * Custom logger for PromoHint module
 */
class Logger extends MonologLogger
{
    /**
     * Logger constructor
     *
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        $name = 'spalenza_promohint',
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
    }
}
