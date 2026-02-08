<?php
namespace Spalenza\PromoHint\Block\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Magento\Framework\UrlInterface;
use Spalenza\PromoHint\Model\Service\PromoHintService;

/**
 * Block for promo hints display in cart
 */
class PromoHint extends Template
{
    /**
     * @var CheckoutCart
     */
    protected $checkoutCart;

    /**
     * @var PromoHintService
     */
    protected $promoHintService;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param CheckoutCart $checkoutCart
     * @param PromoHintService $promoHintService
     * @param array $data
     */
    public function __construct(
        Context $context,
        CheckoutCart $checkoutCart,
        PromoHintService $promoHintService,
        array $data = []
    ) {
        $this->checkoutCart = $checkoutCart;
        $this->promoHintService = $promoHintService;
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context, $data);
    }

    /**
     * Get AJAX URL for loading promo hints
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->urlBuilder->getUrl('spalenza/promohint/ajax', ['_secure' => true]);
    }

    /**
     * Get product IDs from cart
     *
     * @return array
     */
    public function getCartProductIds()
    {
        $productIds = [];

        try {
            $items = $this->checkoutCart->getItems();
            foreach ($items as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }
                $productIds[] = $item->getProductId();
            }
        } catch (\Exception $e) {
            $productIds = [];
        }

        return array_unique($productIds);
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->promoHintService->isEnabled();
    }

    /**
     * Check if there are items in cart
     *
     * @return bool
     */
    public function hasItemsInCart()
    {
        return !empty($this->getCartProductIds());
    }
}
