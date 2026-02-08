<?php
namespace Spalenza\PromoHint\Model;

use Magento\Framework\Model\AbstractModel;
use Spalenza\PromoHint\Api\Data\PromoHintInterface;

class PromoHint extends AbstractModel implements PromoHintInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'spalenza_promo_hint';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'spalenza_promo_hint';

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('Spalenza\PromoHint\Model\ResourceModel\PromoHint');
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return parent::getData(self::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($ruleId)
    {
        return $this->setData(self::RULE_ID, $ruleId);
    }

    /**
     * @inheritdoc
     */
    public function getProductId()
    {
        return parent::getData(self::PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return parent::getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return parent::getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return parent::getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
