<?php
namespace Spalenza\PromoHint\Model\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use Spalenza\PromoHint\Model\ResourceModel\PromoHint\CollectionFactory as PromoHintCollectionFactory;
use Spalenza\PromoHint\Model\PromoHintFactory;
use Spalenza\PromoHint\Model\ResourceModel\PromoHint as PromoHintResource;
use Spalenza\PromoHint\Logger\Logger as PromoHintLogger;

/**
 * Service for managing promo hints
 */
class PromoHintService
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PromoHintCollectionFactory
     */
    protected $promoHintCollectionFactory;

    /**
     * @var PromoHintFactory
     */
    protected $promoHintFactory;

    /**
     * @var PromoHintResource
     */
    protected $promoHintResource;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var PromoHintLogger
     */
    protected $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     * @param PromoHintCollectionFactory $promoHintCollectionFactory
     * @param PromoHintFactory $promoHintFactory
     * @param PromoHintResource $promoHintResource
     * @param DateTime $dateTime
     * @param PromoHintLogger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RuleCollectionFactory $ruleCollectionFactory,
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager,
        PromoHintCollectionFactory $promoHintCollectionFactory,
        PromoHintFactory $promoHintFactory,
        PromoHintResource $promoHintResource,
        DateTime $dateTime,
        PromoHintLogger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->promoHintCollectionFactory = $promoHintCollectionFactory;
        $this->promoHintFactory = $promoHintFactory;
        $this->promoHintResource = $promoHintResource;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'spalenza_promohint/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get display text template
     *
     * @return string
     */
    public function getDisplayTextTemplate()
    {
        return $this->scopeConfig->getValue(
            'spalenza_promohint/general/display_text_template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get maximum rules to display
     *
     * @return int
     */
    public function getMaxRulesDisplay()
    {
        $maxRules = $this->scopeConfig->getValue(
            'spalenza_promohint/general/max_rules_display',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return (int)($maxRules ?? 5);
    }

    /**
     * Update promo hints for a rule
     *
     * @param int $ruleId
     * @return void
     * @throws LocalizedException
     */
    public function updatePromoHintsForRule($ruleId)
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            // Load the rule
            $ruleCollection = $this->ruleCollectionFactory->create();
            $ruleCollection->addFieldToFilter('rule_id', $ruleId);
            /** @var SalesRule $rule */
            $rule = $ruleCollection->getFirstItem();

            if (!$rule || !$rule->getRuleId()) {
                $this->logger->warning("Rule {$ruleId} not found");
                return;
            }

            // Get all product IDs from the rule
            $productIds = $this->extractProductIdsFromRule($rule);

            if (empty($productIds)) {
                $this->logger->info("No products found for rule {$ruleId}");
                return;
            }

            // Get store IDs for this rule
            $storeIds = $rule->getStoreIds();
            if (empty($storeIds) || in_array('0', $storeIds) || in_array(0, $storeIds)) {
                // Rule applies to all stores
                $storeIds = [0];
                foreach ($this->storeManager->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
            }

            // Delete existing hints for this rule
            $this->deletePromoHintsForRule($ruleId);

            // Create new hints
            foreach ($storeIds as $storeId) {
                foreach ($productIds as $productId) {
                    $this->createPromoHint($ruleId, $productId, (int)$storeId);
                }
            }

            $this->logger->info(
                sprintf(
                    'Created %d promo hints for rule %d across %d stores',
                    count($productIds) * count($storeIds),
                    $ruleId,
                    count($storeIds)
                )
            );

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error updating promo hints for rule %d: %s', $ruleId, $e->getMessage()),
                ['exception' => $e]
            );
            throw new LocalizedException(
                __('Error updating promo hints: %1', $e->getMessage())
            );
        }
    }

    /**
     * Delete promo hints for a rule
     *
     * @param int $ruleId
     * @return void
     */
    public function deletePromoHintsForRule($ruleId)
    {
        try {
            $collection = $this->promoHintCollectionFactory->create();
            $collection->addFieldToFilter('rule_id', $ruleId);

            foreach ($collection as $promoHint) {
                $this->promoHintResource->delete($promoHint);
            }

            $this->logger->info("Deleted promo hints for rule {$ruleId}");
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error deleting promo hints for rule %d: %s', $ruleId, $e->getMessage()),
                ['exception' => $e]
            );
        }
    }

    /**
     * Extract product IDs from a sales rule
     *
     * @param SalesRule $rule
     * @return array
     */
    protected function extractProductIdsFromRule(SalesRule $rule)
    {
        $productIds = [];

        try {
            // Get actions condition
            $actions = $rule->getActions();
            if ($actions && is_object($actions)) {
                $actionsData = $actions->getData();
                $productIds = $this->extractProductIdsFromConditions($actionsData);
            }

            // Get conditions
            $conditions = $rule->getConditions();
            if ($conditions && is_object($conditions)) {
                $conditionsData = $conditions->getData();
                $conditionProductIds = $this->extractProductIdsFromConditions($conditionsData);
                $productIds = array_merge($productIds, $conditionProductIds);
            }

            // If product_ids attribute exists (for specific products selection)
            if ($rule->hasData('product_ids')) {
                $ruleProductIds = explode(',', $rule->getData('product_ids'));
                $productIds = array_merge($productIds, $ruleProductIds);
            }

            // Remove duplicates and filter empty values
            $productIds = array_filter(array_unique(array_map('intval', $productIds)));

            $this->logger->info(
                sprintf('Extracted %d products for rule %d', count($productIds), $rule->getRuleId())
            );

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error extracting products for rule %d: %s', $rule->getRuleId(), $e->getMessage()),
                ['exception' => $e]
            );
        }

        return $productIds;
    }

    /**
     * Extract product IDs from conditions array
     *
     * @param array $conditions
     * @return array
     */
    protected function extractProductIdsFromConditions(array $conditions)
    {
        $productIds = [];

        if (isset($conditions['conditions']) && is_array($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $condition) {
                if (isset($condition['type'])) {
                    // Check for SKU-based conditions
                    if (strpos($condition['type'], 'Product\AttributeCombination') !== false ||
                        strpos($condition['type'], 'Product\Found') !== false) {

                        if (isset($condition['value']) && !empty($condition['value'])) {
                            // Try to find products by SKU
                            $skus = is_array($condition['value']) ? $condition['value'] : explode(',', $condition['value']);
                            foreach ($skus as $sku) {
                                $sku = trim($sku);
                                if (!empty($sku)) {
                                    try {
                                        $product = $this->productRepository->get($sku);
                                        $productIds[] = $product->getId();
                                    } catch (\Exception $e) {
                                        $this->logger->warning("Product with SKU {$sku} not found");
                                    }
                                }
                            }
                        }
                    }
                }

                // Recursively process nested conditions
                if (isset($condition['conditions']) && is_array($condition['conditions'])) {
                    $nestedProductIds = $this->extractProductIdsFromConditions($condition);
                    $productIds = array_merge($productIds, $nestedProductIds);
                }
            }
        }

        return $productIds;
    }

    /**
     * Create a promo hint
     *
     * @param int $ruleId
     * @param int $productId
     * @param int $storeId
     * @return void
     */
    protected function createPromoHint($ruleId, $productId, $storeId)
    {
        try {
            $promoHint = $this->promoHintFactory->create();
            $promoHint->setRuleId($ruleId);
            $promoHint->setProductId($productId);
            $promoHint->setStoreId($storeId);
            $this->promoHintResource->save($promoHint);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error creating promo hint: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }
    }

    /**
     * Get promo hints for products
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    public function getPromoHintsForProducts(array $productIds, $storeId)
    {
        if (!$this->isEnabled() || empty($productIds)) {
            return [];
        }

        $hints = [];
        $maxRules = $this->getMaxRulesDisplay();

        try {
            $collection = $this->promoHintCollectionFactory->create();
            $collection->addFieldToFilter('product_id', ['in' => $productIds]);
            $collection->addFieldToFilter('store_id', [0, $storeId]);

            // Join with salesrule to get rule details
            $collection->getSelect()->join(
                ['sr' => $collection->getTable('salesrule')],
                'main_table.rule_id = sr.rule_id',
                ['name', 'description', 'from_date', 'to_date', 'simple_action', 'discount_amount', 'coupon_code']
            );
            $collection->getSelect()->where('sr.is_active = 1');
            $collection->getSelect()->order('main_table.rule_id ASC');

            $productHints = [];
            foreach ($collection as $hint) {
                $productId = $hint->getProductId();
                if (!isset($productHints[$productId])) {
                    $productHints[$productId] = [];
                }

                if (count($productHints[$productId]) < $maxRules) {
                    $productHints[$productId][] = [
                        'rule_id' => $hint->getRuleId(),
                        'product_id' => $productId,
                        'title' => $hint->getName(),
                        'description' => $hint->getDescription(),
                    ];
                }
            }

            // Load product data
            foreach ($productHints as $productId => $rules) {
                try {
                    $product = $this->productRepository->getById($productId);
                    foreach ($rules as $rule) {
                        $hints[] = [
                            'product_id' => $productId,
                            'product_name' => $product->getName(),
                            'product_sku' => $product->getSku(),
                            'title' => $rule['title'],
                            'description' => $rule['description'],
                            'display_text' => $this->replaceTokens(
                                $this->getDisplayTextTemplate(),
                                [
                                    'product_name' => $product->getName(),
                                    'product_sku' => $product->getSku(),
                                    'title' => $rule['title'],
                                    'description' => $rule['description'] ?? '',
                                ]
                            )
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logger->warning(
                        sprintf('Product %d not found: %s', $productId, $e->getMessage())
                    );
                }
            }

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error getting promo hints: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }

        return $hints;
    }

    /**
     * Replace tokens in template
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function replaceTokens($template, array $data)
    {
        $tokens = [
            '%product_name%',
            '%product_sku%',
            '%title%',
            '%description%',
            '%discount_amount%',
            '%coupon_code%'
        ];

        foreach ($tokens as $token) {
            $key = str_replace('%', '', $token);
            $value = $data[$key] ?? '';
            $template = str_replace($token, $value, $template);
        }

        return $template;
    }
}
