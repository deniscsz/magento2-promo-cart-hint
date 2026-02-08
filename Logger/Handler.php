<?php
namespace Spalenza\PromoHint\Logger;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

/**
 * Log handler for PromoHint module
 */
class Handler extends BaseHandler
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/spalenza_promohint.log';

    /**
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;
}
