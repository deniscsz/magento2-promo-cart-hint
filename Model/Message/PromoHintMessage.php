<?php
namespace Spalenza\PromoHint\Model\Message;

/**
 * Message object for promo hint queue processing
 */
class PromoHintMessage
{
    /**
     * @var int
     */
    private $ruleId;

    /**
     * @var string
     */
    private $operation;

    /**
     * @param int $ruleId
     * @param string $operation
     */
    public function __construct($ruleId, $operation)
    {
        $this->ruleId = (int)$ruleId;
        $this->operation = $operation;
    }

    /**
     * @return int
     */
    public function getRuleId()
    {
        return $this->ruleId;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
