<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lofmp_CancelOrder
 * @copyright  Copyright (c) 2021 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */

namespace Lofmp\CancelOrder\Model;

class Email extends \Magento\Framework\Model\AbstractModel
{
    const XML_PATH_ENABLED = 'lofmp_cancelorder/general/enable';
    const XML_PATH_EMAI_TEMPLATE = 'lofmp_cancelorder/general/email_template';
    const XML_PATH_ENABLE_SEND_ADMIN_NOTIFY = 'lofmp_cancelorder/general/notify_admin';
    const XML_PATH_ENABLE_SEND_CUSTOMER_NOTIFY = 'lofmp_cancelorder/general/notify_customer';
    const XML_PATH_ENABLE_SEND_SELLER_NOTIFY = 'lofmp_cancelorder/general/notify_seller';
    const XML_PATH_EMAIL_IDENTITY = 'lofmp_cancelorder/general/sender_name';
    const XML_PATH_ADMIN_EMAIL = 'lofmp_cancelorder/general/admin_email';

    /**
     * Website Model
     *
     * @var \Magento\Store\Model\Website
     */
    protected $_website;

    /**
     * Customer model
     *
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $_customer;

    /**
     * Product alert data
     *
     * @var \Lofmp\CancelOrder\Helper\Data
     */
    protected $_helperData = null;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $_appEmulation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerHelper;

    /**
     * Email constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Lofmp\CancelOrder\Helper\Data $helperData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Helper\View $customerHelper
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Lofmp\CancelOrder\Helper\Data $helperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Helper\View $customerHelper,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_helperData = $helperData;
        $this->_scopeConfig             = $scopeConfig;
        $this->_storeManager            = $storeManager;
        $this->customerRepository       = $customerRepository;
        $this->_appEmulation            = $appEmulation;
        $this->_transportBuilder        = $transportBuilder;
        $this->_customerHelper          = $customerHelper;
        $this->appState = $appState;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Set website model
     *
     * @param \Magento\Store\Model\Website $website
     * @return $this
     */
    public function setWebsite(\Magento\Store\Model\Website $website)
    {
        $this->_website = $website;
        return $this;
    }

    /**
     * Set website id
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        $this->_website = $this->_storeManager->getWebsite($websiteId);
        return $this;
    }

    /**
     * Set customer by id
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->_customer = $this->customerRepository->getById($customerId);
        return $this;
    }

    /**
     * Set customer model
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return $this
     */
    public function setCustomerData($customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    /**
     * Clean data
     *
     * @return $this
     */
    public function clean()
    {
        $this->_customer = null;
        return $this;
    }

    /**
     * Send customer email
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function send()
    {
        if ($this->_website === null) {
            return false;
        }
        if (!$this->_website->getDefaultGroup() || !$this->_website->getDefaultGroup()->getDefaultStore()) {
            return false;
        }
        $store = $this->_website->getDefaultStore();
        $storeId = $store->getId();

        if (!$this->_scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        )){
            return false;
        }

        $this->_appEmulation->startEnvironmentEmulation($storeId);
        $this->_getStockBlock()->setStore($store)->reset();

        $templateId = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $this->_appEmulation->stopEnvironmentEmulation();

        $transport = $this->_transportBuilder->setTemplateIdentifier(
            $templateId
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
        )->setTemplateVars(
            [
                'customerName' => $this->getSubscriberName(),
                'alertGrid' => $alertGrid,
            ]
        )->setFrom(
            $this->_scopeConfig->getValue(
                self::XML_PATH_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->addTo(
            $this->getSubscriberEmail(),
            $this->getSubscriberName()
        )->getTransport();

        $transport->sendMessage();

        return true;
    }

    /**
     * Send admin notification email
     * 
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function sendAdmin()
    {
        if ($this->_website === null) {
            return false;
        }

        if (!$this->_website->getDefaultGroup() || !$this->_website->getDefaultGroup()->getDefaultStore()) {
            return false;
        }
        $store = $this->_website->getDefaultStore();
        $storeId = $store->getId();
        $templateId = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_ADMIN_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $adminEmail = $this->_scopeConfig->getValue(
            self::XML_PATH_ADMIN_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$templateId){
            return false;
        }

        if (!$this->_scopeConfig->getValue(
            self::XML_PATH_ENABLE_SEND_ADMIN_NOTIFY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        )){
            return false;
        }

        if (!$adminEmail) {
            return false;
        }

        $this->_appEmulation->startEnvironmentEmulation($storeId);
        $this->_getStockBlock()->setStore($store)->reset();

        $this->_appEmulation->stopEnvironmentEmulation();

        $_product = $this->getFirstProduct();
        $product_name = $_product ? $_product->getName(): "";
        $product_sku = $_product ? $_product->getSku(): "";

        $transport = $this->_transportBuilder->setTemplateIdentifier(
            $templateId
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
        )->setTemplateVars(
            [
                'subscriberName' => $this->getSubscriberName(),
                'subscriberEmail' => $this->getSubscriberEmail(),
                'message' => $this->getMessage(),
                'url' => $this->getProductUrl(),
                'productName' => $product_name,
                'productSku' => $product_sku
            ]
        )->setFrom(
            $this->_scopeConfig->getValue(
                self::XML_PATH_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->addTo(
            $adminEmail,
            "Owner"
        )->getTransport();

        $transport->sendMessage();

        return true;
    }
    
}
