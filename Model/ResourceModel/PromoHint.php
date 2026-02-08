<?php
namespace Spalenza\PromoHint\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PromoHint extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('spalenza_promo_hints', 'entity_id');
    }
}
