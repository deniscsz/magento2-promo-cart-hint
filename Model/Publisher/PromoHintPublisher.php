<?php
namespace Spalenza\PromoHint\Model\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use Spalenza\PromoHint\Model\Message\PromoHintMessage;
use Spalenza\PromoHint\Logger\Logger as PromoHintLogger;

/**
 * Publisher for promo hint messages
 */
class PromoHintPublisher
{
    const TOPIC_NAME = 'spalenza.promohint.rule.update';

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var PromoHintLogger
     */
    protected $logger;

    /**
     * @param PublisherInterface $publisher
     * @param PromoHintLogger $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        PromoHintLogger $logger
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * Publish message to queue
     *
     * @param int $ruleId
     * @param string $operation
     * @return void
     */
    public function publish($ruleId, $operation)
    {
        try {
            $message = new PromoHintMessage($ruleId, $operation);
            $this->publisher->publish(self::TOPIC_NAME, $message);
            $this->logger->info(
                sprintf('Published message for rule %d with operation %s', $ruleId, $operation)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error publishing message for rule %d: %s', $ruleId, $e->getMessage())
            );
            throw $e;
        }
    }
}
