<?php
namespace Spalenza\PromoHint\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spalenza\PromoHint\Model\Publisher\PromoHintPublisher;
use Spalenza\PromoHint\Logger\Logger as PromoHintLogger;

/**
 * Observer for salesrule delete after event
 */
class AdminhtmlSalesruleDeleteAfter implements ObserverInterface
{
    /**
     * @var PromoHintPublisher
     */
    protected $promoHintPublisher;

    /**
     * @var PromoHintLogger
     */
    protected $logger;

    /**
     * @param PromoHintPublisher $promoHintPublisher
     * @param PromoHintLogger $logger
     */
    public function __construct(
        PromoHintPublisher $promoHintPublisher,
        PromoHintLogger $logger
    ) {
        $this->promoHintPublisher = $promoHintPublisher;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $rule = $observer->getEvent()->getRule();
            if ($rule && $rule->getRuleId()) {
                $this->logger->info(
                    sprintf('SalesRule deleted: %d', $rule->getRuleId())
                );

                // Publish message to queue for async processing
                $this->promoHintPublisher->publish($rule->getRuleId(), 'delete');
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error in salesrule delete observer: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }
    }
}
