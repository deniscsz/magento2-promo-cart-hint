<?php
namespace Spalenza\PromoHint\Model\ResourceModel\PromoHint;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Spalenza\PromoHint\Model\PromoHint as PromoHintModel;
use Spalenza\PromoHint\Model\ResourceModel\PromoHint as PromoHintResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(PromoHintModel::class, PromoHintResource::class);
    }
}
