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
use Magento\Framework\Event\ObserverInterface;
use Magento\Reports\Model\Event;
use Magento\Reports\Observer\EventSaver;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class SubmitRecentViewedProducts implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Reports\Model\Product\Index\ViewedFactory
     */
    protected $_productIndxFactory;

    /**
     * @var EventSaver
     */
    protected $eventSaver;

    /**
     * @var \Magento\Reports\Model\ReportStatus
     */
    private $reportStatus;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reports\Model\Product\Index\ViewedFactory $productIndxFactory
     * @param EventSaver $eventSaver
     * @param \Magento\Reports\Model\ReportStatus $reportStatus
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reports\Model\Product\Index\ViewedFactory $productIndxFactory,
        EventSaver $eventSaver,
        \Magento\Reports\Model\ReportStatus $reportStatus,
        GetCustomer $getCustomer
    ) {
        $this->_storeManager = $storeManager;
        $this->_productIndxFactory = $productIndxFactory;
        $this->eventSaver = $eventSaver;
        $this->reportStatus = $reportStatus;
        $this->getCustomer = $getCustomer;
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
        if (!$this->reportStatus->isReportEnabled(Event::EVENT_PRODUCT_VIEW)) {
            throw new GraphQlNoSuchEntityException(__('The function is not support.'));
        }
        /** @var ContextInterface $context */
        if (!$context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        $productIds = isset($args['product_ids']) ? $args['product_ids'] : [];
        if (empty($productIds)) {
            throw new GraphQlInputException(__('Missing value for input field product_ids.'));
        }
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $customer = $this->getCustomer->execute($context);

        $limit = 20;
        $i = 1;
        $totalResult = 0;
        foreach ($productIds as $productId) {
            if ($i <= $limit) {
                $viewData = [];
                $viewData['product_id'] = $productId;
                $viewData['store_id']   = $storeId;
                $viewData['customer_id'] = $customer->getId();

                $this->_productIndxFactory->create()->setData($viewData)->save()->calculate();
                $this->eventSaver->save(Event::EVENT_PRODUCT_VIEW, $productId);
                $totalResult++;
            }
            $i++;
        }

        return [
            "message" => __("Recent viewed products were added!"),
            "total_count" => $totalResult
        ];
    }
}

