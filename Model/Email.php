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
    const XML_PATH_EMAIL_TEMPLATE = 'lofmp_cancelorder/general/email_template';
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
     * @var mixed|array
     */
    protected $_data = [];

    /**
     * @var string|null
     */
    protected $_receiver_name = null;

    /**
     * @var string|null
     */
    protected $_receiver_email = null;

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
     * Set email data
     *
     * @param mixed|array $data
     * @return $this
     */
    public function setEmailData($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setReciverEmail($email)
    {
        $this->_receiver_email = $email;
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setReciverName($name)
    {
        $this->_receiver_name = $name;
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
        $this->_receiver_email = null;
        $this->_receiver_name = null;
        $this->_data = [];
        return $this;
    }

    /**
     * Send customer email
     *
     * @param bool $is_send_admin
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function send($is_send_admin = false)
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

        if ($is_send_admin) {
            $admin_email = $this->_scopeConfig->getValue(
                self::XML_PATH_ADMIN_EMAIL,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $this->setReciverEmail($admin_email);
        }

        if (!$this->_data || !$this->_receiver_email) {
            return false;
        }

        $templateId = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $transport = $this->_transportBuilder->setTemplateIdentifier(
            $templateId
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
        )->setTemplateVars(
            $this->_data
        )->setFrom(
            $this->_scopeConfig->getValue(
                self::XML_PATH_EMAIL_IDENTITY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->addTo(
            $this->_receiver_email,
            $this->_receiver_name
        )->getTransport();

        $transport->sendMessage();

        return true;
    }
}
