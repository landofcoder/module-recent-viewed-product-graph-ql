<?php
/**
 * Landofcoder
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * https://landofcoder.com/terms
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category   Landofcoder
 * @package    Lof_RecentViewedGraphQl
 * @copyright  Copyright (c) 2022 Landofcoder (https://www.landofcoder.com/)
 * @license    https://landofcoder.com/terms
 */

namespace Lof\RecentViewedGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;

class RecentViewedProducts implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var \Magento\Reports\Block\Product\Viewed
     */
    protected $recentlyViewed;

    /**
     * @var ProductQueryInterface
     */
    private $searchQuery;

    /**
     * @param \Magento\Reports\Block\Product\Viewed $recentlyViewed
     * @param GetCustomer $getCustomer
     * @param ProductQueryInterface $searchQuery
     */
    public function __construct(
        \Magento\Reports\Block\Product\Viewed $recentlyViewed,
        GetCustomer $getCustomer,
        ProductQueryInterface $searchQuery
    ) {
        $this->recentlyViewed = $recentlyViewed;
        $this->getCustomer = $getCustomer;
        $this->searchQuery = $searchQuery;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        /** @var ContextInterface $context */
        if (!$context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        $pageSize = isset($args['pageSize']) ? (int)$args['pageSize'] : 5;
        $currentPage = isset($args['currentPage']) ? $args['currentPage'] : 1;

        $customer = $this->getCustomer->execute($context);
        $collection = $this->recentlyViewed->getItemsCollection();
        $collection->setCustomerId($customer->getId());
        //echo $collection->getSelect();die(" sss");
        $collection->addFieldToFilter("item_store_id", $storeId);
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $items = $collection->load();

        $searchResult = null;
        $filterSkus = [];

        foreach ($items as $product)
        {
            $filterSkus[] = $product->getSku();
        }

        if (!empty($filterSkus)) {
            $newArgs = $args;
            $newArgs['filter'] = [
                "sku" => [
                    "in" => $filterSkus
                ]
            ];
            $searchResult = $this->searchQuery->getResult($newArgs, $info, $context);

            if ($searchResult->getCurrentPage() > $searchResult->getTotalPages() && $searchResult->getTotalCount() > 0) {
                throw new GraphQlInputException(
                    __(
                        'currentPage value %1 specified is greater than the %2 page(s) available.',
                        [$searchResult->getCurrentPage(), $searchResult->getTotalPages()]
                    )
                );
            }
        }
        $totalCount =  $searchResult ? $searchResult->getTotalCount() : 0 ;
        $totalPages = $pageSize ? ((int)ceil($totalCount / $pageSize)) : 0;

        return [
            "items" => $searchResult ? $searchResult->getProductsSearchResult() : [],
            "total_count" =>  $totalCount,
            "currentPage" => $currentPage,
            "pageSize" => $pageSize,
            "totalPages" => $totalPages
        ];
    }
}

