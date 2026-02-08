<?php
namespace Spalenza\PromoHint\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Magento\Store\Model\StoreManagerInterface;
use Spalenza\PromoHint\Model\Service\PromoHintService;
use Spalenza\PromoHint\Logger\Logger as PromoHintLogger;

/**
 * AJAX controller for promo hints
 */
class Ajax extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var FormKeyValidator
     */
    protected $formKeyValidator;

    /**
     * @var CheckoutCart
     */
    protected $checkoutCart;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PromoHintService
     */
    protected $promoHintService;

    /**
     * @var PromoHintLogger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param CheckoutCart $checkoutCart
     * @param StoreManagerInterface $storeManager
     * @param PromoHintService $promoHintService
     * @param PromoHintLogger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        CheckoutCart $checkoutCart,
        StoreManagerInterface $storeManager,
        PromoHintService $promoHintService,
        PromoHintLogger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->checkoutCart = $checkoutCart;
        $this->storeManager = $storeManager;
        $this->promoHintService = $promoHintService;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // Validate form key
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Invalid form key')
                ]);
            }

            // Check if module is enabled
            if (!$this->promoHintService->isEnabled()) {
                return $result->setData([
                    'success' => true,
                    'hints' => []
                ]);
            }

            // Get product IDs from cart
            $productIds = $this->getCartProductIds();

            if (empty($productIds)) {
                return $result->setData([
                    'success' => true,
                    'hints' => []
                ]);
            }

            // Get promo hints for products
            $storeId = $this->storeManager->getStore()->getId();
            $hints = $this->promoHintService->getPromoHintsForProducts($productIds, $storeId);

            $this->logger->info(
                sprintf('Returning %d promo hints for %d products', count($hints), count($productIds))
            );

            return $result->setData([
                'success' => true,
                'hints' => $hints
            ]);

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error in promo hint AJAX: %s', $e->getMessage()),
                ['exception' => $e]
            );

            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while loading promo hints')
            ]);
        }
    }

    /**
     * Get product IDs from cart
     *
     * @return array
     */
    protected function getCartProductIds()
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
            $this->logger->error(
                sprintf('Error getting cart items: %s', $e->getMessage())
            );
        }

        return array_unique($productIds);
    }
}
