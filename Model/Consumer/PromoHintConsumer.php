<?php
namespace Spalenza\PromoHint\Model\Consumer;

use Spalenza\PromoHint\Model\Message\PromoHintMessage;
use Spalenza\PromoHint\Model\Service\PromoHintService;
use Spalenza\PromoHint\Logger\Logger as PromoHintLogger;

/**
 * Consumer for processing promo hint messages
 */
class PromoHintConsumer
{
    /**
     * @var PromoHintService
     */
    protected $promoHintService;

    /**
     * @var PromoHintLogger
     */
    protected $logger;

    /**
     * @param PromoHintService $promoHintService
     * @param PromoHintLogger $logger
     */
    public function __construct(
        PromoHintService $promoHintService,
        PromoHintLogger $logger
    ) {
        $this->promoHintService = $promoHintService;
        $this->logger = $logger;
    }

    /**
     * Process promo hint message
     *
     * @param PromoHintMessage $message
     * @return void
     */
    public function process(PromoHintMessage $message)
    {
        try {
            $ruleId = $message->getRuleId();
            $operation = $message->getOperation();

            $this->logger->info("Processing rule {$ruleId} with operation {$operation}");

            if ($operation === 'delete') {
                $this->promoHintService->deletePromoHintsForRule($ruleId);
                $this->logger->info("Deleted promo hints for rule {$ruleId}");
            } else {
                // save, update, create
                $this->promoHintService->updatePromoHintsForRule($ruleId);
                $this->logger->info("Updated promo hints for rule {$ruleId}");
            }

        } catch (\Exception $e) {
            $this->logger->error(
                'Error processing promo hint message: ' . $e->getMessage(),
                ['exception' => $e]
            );
            throw $e; // Re-throw for queue retry
        }
    }
}
