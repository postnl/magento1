<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@postnl-plugins.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@postnl-plugins.nl for more information.
 *
 * @copyright   Copyright (c) 2019 DM Productions B.V. (https://www.dmp.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class DMP_PostNL_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Address type used by PakjeGemak addresses.
     */
    const PG_ADDRESS_TYPE = 'pakje_gemak';

	/**
	 * Regular expression used to split street name from house number.
	 *
	 * For the full description go to:
	 * @link https://gist.github.com/RichardPerdaan/1e6ce1588f3990e856b55255572692d1
	 */
    const SPLIT_STREET_REGEX = '~(?P<street>.*?)\s?(?P<street_suffix>(?P<number>[\d]+)[\s-]{0,2}(?P<extension>[a-zA-Z/\s]{0,5}$|[0-9/]{0,5}$|\s[a-zA-Z]{1}[0-9]{0,3}$|\s[0-9]{2}[a-zA-Z]{0,3}$))$~';

    /**
     * Log filename to log all non-specific PostNL exceptions.
     */
    const POSTNL_EXCEPTION_LOG_FILE = 'DMP_PostNL_Exception.log';

    /**
     * Log filename to log all non-specific PostNL debug messages.
     */
    const POSTNL_DEBUG_LOG_FILE = 'DMP_PostNL_Debug.log';

    /**
     * email address of the shop owner
     */
    const XML_PATH_EMAIL_IDENTITY = 'sales_email/order/identity';
    /**
     * Localised track and trace base URL's
     */
    const POSTNL_TRACK_AND_TRACE_NL_BASE_URL = 'https://mijnpakket.postnl.nl/Inbox/Search?';
    const POSTNL_TRACK_AND_TRACE_INT_BASE_URL = 'https://www.internationalparceltracking.com/Main.aspx#/track';

    /**
     * List of PostNL shipping methods.
     *
     * @var null|array
     */
    protected $_postNLShippingMethods = null;

    /**
     * Gets a config value for this module, automatically selecting the current store.
     *
     * @param string $value
     * @param string $group
     * @param int    $storeId to use in the backend, e.g. $order->getStoreId()
     * @param bool   $decrypt
     *
     * @return string
     */
    public function getConfig($value, $group = 'general', $storeId = null, $decrypt = false)
    {
        if (empty($storeId)) { // in case of frontend calls
            $storeId = Mage::app()->getStore()->getId();
        }
        $config = Mage::getStoreConfig('dmp_postnl/' . $group . '/' . $value, $storeId);

        if ($decrypt) {
            $config = Mage::helper('core')->decrypt($config);
        }

        return trim($config);
    }

    /**
     * Gets a PakjeGemak address for either a quote or an order object.
     *
     * @param Mage_Sales_Model_Quote|Mage_Sales_Model_Order $object
     *
     * @return false|Mage_Sales_Model_Order_Address|Mage_Sales_Model_Quote_Address|DMP_PostNL_Model_Shipment
     */
    public function getPgAddress($object)
    {
        /**
         * Get all addresses for the specified object.
         */
        if ($object instanceof Mage_Sales_Model_Quote) {
            $addressCollection = $object->getAllAddresses();
        } elseif ($object instanceof Mage_Sales_Model_Order) {
            $addressCollection = $object->getAddressesCollection();
        } elseif ($object instanceof DMP_PostNL_Model_Shipment) {
            $order = $object->getOrder();

            if (!$order) {
                return false;
            }

            $addressCollection = $order->getAddressesCollection();
        } else {
            return false;
        }

        /**
         * Go through each address and check if it's a PakjeGemak address.
         *
         * @var Mage_Sales_Model_Quote_Address|Mage_Sales_Model_Order_Address $address
         */
        $pgAddress = false;
        foreach ($addressCollection as $address) {
            if ($address->getAddressType() == self::PG_ADDRESS_TYPE) {
                $pgAddress = $address;
                break;
            }
        }

        /**
         * Return the PakjeGemak address or false if none was found.
         */

        return $pgAddress;
    }

    /**
     * Gets a list of PostNL shipping methods.
     *
     * @return array
     */
    public function getPostNLShippingMethods()
    {
        if ($this->_postNLShippingMethods == null) {
            $shippingMethods = $this->getConfig('postnl_shipping_methods');
            $shippingMethods = explode(',', $shippingMethods);

            $this->_postNLShippingMethods = $shippingMethods;
        }

        return $this->_postNLShippingMethods;
    }

    /**
     * Checks if a given shipping method is PostNL.
     *
     * @param string $method
     *
     * @return boolean
     */
    public function shippingMethodIsPostNL($method)
    {
        if ($this->getConfig('always_postnl') === '1') {
            return true;
        }

        $postNLShippingMethods = $this->getPostNLShippingMethods();

        $method = 'bolcom_bolcom' === $method ? 'bolcom_flatrate' : $method;
        $method = strpos($method, 'matrixrate_matrixrate') !== false ? 'matrixrate_matrixrate' : $method;
        if (in_array($method, $postNLShippingMethods)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given shipping has extra options
     *
     * @param $method
     *
     * @return bool
     */
    public function shippingHasExtraOptions($method)
    {

        $postNLCarrier = Mage::getModel('dmp_postnl/carrier_postNL');
        $postNLCode = $postNLCarrier->getCarrierCode();

		if (
            strpos($method, $postNLCode) === 0 && // Check if PostNL is used
			($method == $postNLCode . '_tablerate' || $method == $postNLCode . '_flatrate')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given shipping method is Pakjegemak
     *
     * @param $method
     *
     * @return bool
     */
    public function shippingMethodIsPakjegemak($method)
    {
        $postNLCarrier = Mage::getModel('dmp_postnl/carrier_postNL');
        $postNLCode = $postNLCarrier->getCarrierCode();

        if ($method == $postNLCode . '_pakjegemak' || $method == $postNLCode . '_pickup' || $method == $postNLCode . '_pickup_express') {
            return true;
        }

        return false;
    }

    /**
     * Get html for the PostNL options
     *
     * @param DMP_PostNL_Model_Shipment $postNLShipment
     *
     * @return string
     */
    public function getCurrentOptionsHtml($postNLShipment)
    {
        $options = array(
            $this->__(ucfirst(str_replace('_', ' ', $postNLShipment->getShipmentType()))),
        );

        if ($postNLShipment->getShipmentType() == 'normal') {

            if ($postNLShipment->getHomeAddressOnly() == '1')
                $options[] = $this->__('Home address only');

            if ($postNLShipment->getHomeAddressOnly() == '1')
                $options[] = $this->__('Signature on receipt');

            if ($postNLShipment->getReturnIfNoAnswer() == '1')
                $options[] = $this->__('Return if no answer');

            if ($postNLShipment->getInsuredAmount() > 0)
                $options[] = $this->__('Insured up to &euro;%s', $postNLShipment->getInsuredAmount());

            if ($postNLShipment->getIsXL() == '1')
                $options[] = $this->__('Large package');

        }

        $htmlOptions = $this->__('status_' . $postNLShipment->getStatus()) . ', ' . strtolower(implode(', ', $options));

        return $htmlOptions;
    }

    /**
     * Returns the whiteList codes for customs.
     * @return array
     */
    public function whiteListCodes()
    {
        return array(
            'NL', 'BE', 'BG', 'DK', 'DE', 'EE', 'FI', 'FR', 'HU', 'IE',
            'IT', 'LV', 'LT', 'LU', 'MC', 'AT', 'PL', 'PT', 'RO', 'SI',
            'SK', 'ES', 'CZ', 'SE'
        );
    }

    /**
     * Checks if country needs to have customs
     *
     * @param $countryCode
     *
     * @return bool
     */
    public function countryNeedsCustoms($countryCode)
    {
        $whitelisted = in_array($countryCode, $this->whiteListCodes());
        if (!$whitelisted) {
            return true;
        }

        return false;
    }

    /**
     * Constructs a track & trace url based on a barcode and the destination of the package (country and zipcode)
     *
     * @param string           $barcode
     * @param mixed            $destination An array or object containing the shipment's destination data
     * @param boolean | string $lang
     * @param boolean          $forceNl
     *
     * @return string
     */
    public function getBarcodeUrl($barcode, $destination = false, $lang = false, $forceNl = false)
    {
        $countryCode = null;
        $postcode = null;
        if (is_array($destination)) {
            if (!isset($destination['countryCode'])) {
                throw new InvalidArgumentException("Destination must contain a country code.");
            }

            $countryCode = $destination['countryCode'];
            $postcode = $destination['postcode'];
        } elseif (is_object($destination) && $destination instanceof Varien_Object) {
            if (!$destination->getCountry()) {
                throw new InvalidArgumentException('Destination must contain a country code.');
            }

            $countryCode = $destination->getCountry();
            $postcode = str_replace(' ', '', $destination->getPostcode());
        } else {
            throw new InvalidArgumentException('Destination must be an array or an instance of Varien_Object.');
        }

        /**
         * Get the dutch track & trace URL for dutch shipments or for the admin.
         */
        if ($forceNl
            || (!empty($countryCode)
                && $countryCode == 'NL'
            )
        ) {
            $barcodeUrl = self::POSTNL_TRACK_AND_TRACE_NL_BASE_URL
                . '&b=' . $barcode;
            /**
             * For dutch shipments add the postcode. For international shipments add an 'international' flag.
             */
            if (!empty($postcode)
                && !empty($countryCode)
                && $countryCode == 'NL'
            ) {
                $barcodeUrl .= '&p=' . $postcode;
            } else {
                $barcodeUrl .= '&i=true';
            }

            return $barcodeUrl;
        }

        /**
         * Get a general track & trace URL for all other destinations.
         */
        $barcodeUrl = self::POSTNL_TRACK_AND_TRACE_INT_BASE_URL
            . '/' . $barcode
            . '/' . $countryCode;

        if (!empty($postcode)) {
            $barcodeUrl .= '/' . $postcode;
        }

        return $barcodeUrl;
    }

    /**
     * Retrieves street name, house number and house number extension from the shipping address.
     * The shipping address may be in multiple street lines configuration or single line configuration. In the case of
     * multi-line, each part of the street data will be in a separate field. In the single line configuration, each part
     * will be in the same field and will have to be split using PREG.
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     *
     * @param bool                                 $throwException
     *
     * @return array
     * @throws DMP_PostNL_Exception
     */
    public function getStreetData($address, $throwException = true)
    {

        $fullStreet = $address->getStreetFull();

        if ($address->getCountry() != 'NL' && $address->getCountry() != 'BE') {

            $fullStreet = $this->_getInternationalFullStreet($address);
            $streetData = array(
                'streetname' => $fullStreet,
                'housenumber' => '',
                'housenumberExtension' => '',
                'fullStreet' => $fullStreet,
            );
            return $streetData;
        }

        /**
         * Split the address using PREG.
         * @var DMP_PostNL_Helper_Data $this
         */
        $streetData = $this->_getSplitStreetData($fullStreet, $throwException);

        return $streetData;
    }

    /**
     * Splits street data into separate parts for street name, housenumber and extension.
     *
     * @param string $fullStreet The full street name including all parts
     *
     * @param bool   $throwException
     *
     * @return array
     * @throws DMP_PostNL_Exception
     */
    protected function _getSplitStreetData($fullStreet, $throwException = true)
    {
        $fullStreet = preg_replace("/[\n\r]/", " ", $fullStreet);

        if (strlen($fullStreet) > 40 && $throwException) {
            throw new DMP_PostNL_Exception(
                $this->__('Address is too long. Make the delivery address less than 40 characters.'),
                'MYPA-0026'
            );
        }

        $result = preg_match(self::SPLIT_STREET_REGEX, $fullStreet, $matches);

        if (!$result || !is_array($matches) || (isset($matches[0]) && $fullStreet != $matches[0])) {
            if (isset($matches[0]) && $fullStreet != $matches[0] && $throwException) {
                // Characters are gone by preg_match
                throw new DMP_PostNL_Exception(
                    $this->__('Something went wrong with splitting up address %s.', $fullStreet),
                    'MYPA-0026'
                );
            } elseif ($throwException) {
                // Invalid full street supplied
                throw new DMP_PostNL_Exception(
                    $this->__('Invalid full street supplied: %s.', $fullStreet),
                    'MYPA-0005'
                );
            } else {
                return false;
            }
        }

        $streetname = '';
        $housenumber = '';
        if (isset($matches['street'])) {
            $streetname = $matches['street'];
        }

        if (isset($matches['number'])) {
            $housenumber = $matches['number'];
        }

        if (isset($matches['extension'])) {
            $housenumberExtension = $matches['extension'];
        } else {
            $housenumberExtension = '';
        }

        $streetData = array(
            'streetname' => $streetname,
            'housenumber' => $housenumber,
            'housenumberExtension' => $housenumberExtension,
            'fullStreet' => $fullStreet,
        );

        return $streetData;
    }

    /**
     * Get total weight
     *
     * @param $products
     *
     * @return float|int
     */
    public function getTotalWeight($products)
    {
        $totalWeight = false;
        /** @var Mage_Sales_Model_Order_Item $product */
        foreach ($products as $product) {
            if ($product->canShip()) {
                $totalWeight = $totalWeight + (float)$product->getData('weight') * ($product->getData('qty_ordered') - $product->getData('qty_shipped'));
            }
        }

        return $totalWeight;
    }

    /**
     * @param        $items
     * @param string $country
     * @param bool   $getAdminTitle
     * @param bool   $hasExtraOptions
     * @param bool   $isFrontend If mailbox title is empty, don't show the mailbox option
     *
     * @return int|string               package = 1, mailbox = 2, letter = 3
     */
    public function getPackageType($items, $country, $getAdminTitle = false, $hasExtraOptions = false, $isFrontend = false)
    {
        $country = $country === false ? 'NL' : $country;
        $mailboxActive = $this->getConfig('mailbox_active', 'mailbox') == '' ? false : true;

        if ($mailboxActive) {

            $hideMailboxInFrontend = $this->getConfig('mailbox_title', 'mailbox') == '' && $isFrontend ? true : false;
            if ($hasExtraOptions || $hideMailboxInFrontend == true) {
                $type = 1;
            } else {
                $fitInLetterbox = $this->fitInLetterbox($items);
                $type = $fitInLetterbox && $country == 'NL' ? 2 : 1;
            }
        } else {
            $type = 1;
        }

        if ($getAdminTitle) {
            return $type == 1 ? $this->__('Normal') : $this->__('Letter box');
        }

        return $type;
    }

    /**
     * @param $items
     *
     * @return bool
     */
    public function productIsAvailable($items)
    {
        foreach ($items as $item) {
            if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                /** @var Mage_Sales_Model_Order_Item $item */
                $id = $item->getProductId();
            } else {
                /** @var Mage_Sales_Model_Quote_Address_Item $item */
                $id = $item->getProduct()->getId();
            }
            $itemAttributeVolume = Mage::getModel('catalog/product')
                ->load($id)
                ->getData('show_postnl_options');

            if ($itemAttributeVolume == "2") {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Entity_Quote_Item_Collection|Mage_Sales_Model_Entity_Order_Item_Collection $items
     *
     * @return bool
     */
    private function fitInLetterbox($items)
    {
        if ($this->getConfig('mailbox_active', 'mailbox') == '0')
            return false;

        $mailboxWeight = (float)$this->getConfig('mailbox_weight', 'mailbox');
        $itemWeight = 0;

        foreach ($items as $item) {
            $qty = $item->getQty();
            $qty = $qty == null ? $item->getData('qty_ordered') : $qty;
            if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                /** @var Mage_Sales_Model_Order_Item $item */
                $id = $item->getProductId();
            } else {
                /** @var Mage_Sales_Model_Quote_Address_Item $item */
                $id = $item->getProduct()->getId();
            }
            $itemAttributeVolume = Mage::getModel('catalog/product')
                ->load($id)
                ->getData('postnl_mailbox_volume');

            $itemVolume = (float)$itemAttributeVolume * $qty;

            if ($itemVolume > 0) {
                $itemWeight += $itemVolume / 100 * $mailboxWeight;
            } else {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($id);
                if (empty($parentIds)) {
                    $itemWeight += $item->getWeight() * $qty;
                }
            }
        }

        return $itemWeight <= $mailboxWeight ? true : false;
    }

    /**
     * Get multiple HS codes from categories or default settings
     *
     * @param $products
     * @param $_storeId
     *
     * @return string
     */
    public function getHsCodes($products, $_storeId)
    {
        $hs = array();
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($products as $item) {
            $hs[$this->getHsCode($item, $_storeId)] = $this->getHsCode($item, $_storeId);
        }

        if (empty($hs)) {
            return $this->getConfig('customs_type', 'shipment', $_storeId);
        } else {
            return implode(',', $hs);
        }
    }

    /**
     * Get HS code from categories or default settings
     *
     * @param $item
     * @param $_storeId
     *
     * @return string
     */
    public function getHsCode($item, $_storeId)
    {
        $hs = '';
        /** @var Mage_Sales_Model_Order_Item $item */
        /** @var Mage_Catalog_Model_Category $category */
        foreach ($item->getProduct()->getCategoryIds() as $categoryId) {
            $cat = Mage::getModel('catalog/category')->load($categoryId);
            if ($cat->getHs() && $cat->getHs() > 1000 && $cat->getHs() < 9999) {
                $hs = $cat->getHs();
            }
        }

        if ($hs == '') {
            return $this->getConfig('customs_type', 'shipment', $_storeId);
        } else {
            return $hs;
        }
    }

    /**
     * Generate the entire global address at two address fields
     *
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return string
     */
    protected function _getInternationalFullStreet($address)
    {
        if (!$address->getStreet2() ||
            $this->getConfig('prefix_street2_international_addresses', 'shipment') === '0'
        ) {
            return preg_replace("/[\n\r]/", " ", $address->getStreetFull());
        }

        $numberBeforeStreetCountry = array('CN', 'FR', 'GR', 'IE', 'IL', 'JP', 'LU', 'MY', 'MA', 'NZ', 'SG', 'GB', 'US', 'VS');
        if (in_array($address->getCountry(), $numberBeforeStreetCountry)) {
            return $address->getStreet2() . ' ' . $address->getStreet1();
        } else {
            return preg_replace("/[\n\r]/", " ", $address->getStreetFull());
        }
    }

    /**
     * Checks if the current edition of Magento is enterprise. Uses Mage::getEdition if available. If not, look for the
     * Enterprise_Enterprise extension. Finally, check the version number.
     *
     * @return boolean
     */
    public function isEnterprise()
    {
        /**
         * Use Mage::getEdition, which is available since CE 1.7 and EE 1.12.
         */
        if (method_exists('Mage', 'getEdition')) {
            $edition = Mage::getEdition();
            if ($edition == Mage::EDITION_ENTERPRISE) {
                return true;
            }

            return false;
        }

        /**
         * Check if the Enterprise_Enterprise extension is installed.
         */
        if (Mage::getConfig()->getNode('modules')->Enterprise_Enterprise) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the current environment is in the shop's admin area.
     *
     * @return boolean
     */
    public function isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return true;
        }

        /**
         * Fallback check in case the previous check returns a false negative.
         */
        if (Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }

        return false;
    }

    /**
     * Logs a debug message. Based on Mage::log.
     *
     * @param string      $message
     * @param int|null    $level
     * @param string|null $file
     * @param boolean     $forced
     * @param boolean     $isError
     *
     * @return $this
     *
     * @see Mage::log
     */
    public function log($message, $level = null, $file = null, $forced = false, $isError = false)
    {
        if (is_null($level)) {
            $level = Zend_Log::DEBUG;
        }

        if (is_null($file)) {
            $file = self::POSTNL_DEBUG_LOG_FILE;
        }

        Mage::log($message, $level, $file, $forced);

        return $this;
    }

    /**
     * Logs a PostNL Exception. Based on Mage::logException.
     *
     * @param string|Exception $exception
     *
     * @return $this
     *
     * @see Mage::logException
     */
    public function logException($exception)
    {
        if (is_object($exception)) {
            $message = "\n" . $exception->__toString();
        } else {
            $message = $exception;
        }

        $file = self::POSTNL_EXCEPTION_LOG_FILE;

        $this->log($message, Zend_Log::ERR, $file, false, true);

        return $this;
    }

    /**
     * Add a message to the specified session. Message can be an error, a success message, an info message or a warning.
     * If a valid error code is supplied, the message will be prepended with the error code and a link to a
     * knowledgebase article will be appended.
     *
     * If no $code is specified, $messageType and $message will be required
     *
     * @param string|Mage_Core_Model_Session_Abstract $session The session to which the messages will be added.
     * @param string|null                             $code
     * @param string|null                             $messageType
     * @param string|null                             $message
     *
     * @return $this
     *
     * @see Mage_Core_Model_Session_Abstract::addMessage()
     *
     * @throws InvalidArgumentException
     * @throws DMP_PostNL_Exception
     */
    public function addSessionMessage($session, $code = null, $messageType = null, $message = null)
    {
        /***************************************************************************************************************
         * Check that the required arguments are available and valid.
         **************************************************************************************************************/

        /**
         * If $code is null or 0, $messageType and $message are required.
         */
        if (
            (is_null($code) || $code === 0)
            && (is_null($messageType) || is_null($message))
        ) {
            throw new InvalidArgumentException(
                "Warning: Missing argument for addSessionMessage method: 'messageType' and 'message' are required."
            );
        }

        /**
         * If the session is a string, treat it as a class name and instantiate it.
         */
        if (is_string($session) && strpos($session, '/') !== false) {
            $session = Mage::getSingleton($session);
        } elseif (is_string($session)) {
            $session = Mage::getSingleton($session . '/session');
        }

        /**
         * If the session could not be loaded or is not of the correct type, throw an exception.
         */
        if (!$session
            || !is_object($session)
            || !($session instanceof Mage_Core_Model_Session_Abstract)
        ) {
            throw new DMP_PostNL_Exception(
                $this->__('Invalid session requested.'),
                'MYPA-0007'
            );
        }

        $errorMessage = $this->getSessionMessage($code, $messageType, $message);

        /***************************************************************************************************************
         * Add the error to the session.
         **************************************************************************************************************/

        /**
         * The method we'll use to add the message to the session has to be built first.
         */
        $addMethod = 'add' . ucfirst($messageType);

        /**
         * If the method doesn't exist, throw an exception.
         */
        if (!method_exists($session, $addMethod)) {
            throw new DMP_PostNL_Exception(
                $this->__('Invalid message type requested: %s.', $messageType),
                'MYPA-0008'
            );
        }

        /**
         * Add the message to the session.
         */
        $session->$addMethod($errorMessage);

        return $this;
    }

    /**
     * Formats a message string so it can be added as a session message.
     *
     * @param null|string $code
     * @param null|string $messageType
     * @param null|string $message
     *
     * @return string
     *
     * @throws DMP_PostNL_Exception
     * @throws InvalidArgumentException
     */
    public function getSessionMessage($code = null, $messageType = null, $message = null)
    {
        /**
         * If $code is null or 0, $messageType and $message are required.
         */
        if (
            (is_null($code) || $code === 0)
            && (is_null($messageType) || is_null($message))
        ) {
            throw new InvalidArgumentException(
                "Warning: Missing argument for addSessionMessage method: 'messageType' and 'message' are required."
            );
        }

        /***************************************************************************************************************
         * Get the actual error from config.xml if it's available.
         **************************************************************************************************************/

        $error = false;
        $link = false;

        if (!is_null($code) && $code !== 0) {
            /**
             * get the requested code and if possible, the knowledgebase link
             */
            $error = Mage::getConfig()->getNode('tig/errors/' . $code);
            if ($error !== false) {
                $link = (string)$error->url;
            }
        }

        /***************************************************************************************************************
         * Check that the required 'message' and 'messageType' components are available. If they are not yet available,
         * we'll try to read them from the error itself.
         **************************************************************************************************************/

        /**
         * If the specified error was found and no message was supplied, get the error's default message.
         */
        if ($error && !$message) {
            $message = (string)$error->message;
        }

        /**
         * If we still don't have a valid message, throw an exception.
         */
        if (!$message) {
            throw new DMP_PostNL_Exception(
                $this->__('No message supplied.'),
                'MYPA-0009'
            );
        }

        /**
         * If the specified error was found and no message type was supplied, get the error's default type.
         */
        if ($error && !$messageType) {
            $messageType = (string)$error->type;
        }


        /**
         * If we still don't have a valid message type, throw an exception.
         */
        if (!$messageType) {
            throw new DMP_PostNL_Exception(
                $this->__('No message type supplied.'),
                'MYPA-0010'
            );
        }

        /***************************************************************************************************************
         * Build the actual message we're going to add. The message will consist of the error code, followed by the
         * actual message and finally a link to the knowledge base. Only the message part is required.
         **************************************************************************************************************/

        /**
         * Lets start with the error code if it's present. It will be formatted as "[POSTNL-0001]".
         */
        $errorMessage = '';
        if (!is_null($code)
            && $code !== 0
        ) {
            $errorMessage .= "[{$code}] ";
        }

        /**
         * Add the actual message. This is the only required part. The code and link are optional.
         */
        $errorMessage .= $this->__($message);

        /**
         * Add the link to the knowledgebase if we have one.
         */
        if ($link) {
            $errorMessage .= ' <a href="'
                . $link
                . '" target="_blank" class="postnl-message">'
                . $this->__('Click here for more information from the TiG knowledgebase.')
                . '</a>';
        }

        return $errorMessage;
    }

    /**
     * Adds an error message to the specified session based on an exception. The exception should contain a valid error
     * code in order to properly process the error. Exceptions without a (valid) error code will behave like a regular
     * $session->addError() call.
     *
     * @param string|Mage_Core_Model_Session_Abstract $session The session to which the messages will be added.
     * @param Exception                               $exception
     *
     * @return $this
     */
    public function addExceptionSessionMessage($session, Exception $exception)
    {
        /**
         * Get the error code, message type (hardcoded as 'error') and the message of the exception
         */
        $messageType = 'error';
        $exceptionMessage = trim($exception->getMessage());
        $message = $this->__('An error occurred while processing your request: ') . $exceptionMessage;
        $code = $exception->getCode();
        if (empty($code)) {
            $code = $this->getErrorCodeByMessage($exceptionMessage);
        }

        return $this->addSessionMessage($session, $code, $messageType, $message);
    }

    /**
     * Gets an error code by looping through all known errors and if the specified message can be matched, returning the
     * associated code.
     *
     * @param string $message
     *
     * @return string|null
     */
    public function getErrorCodeByMessage($message)
    {
        /**
         * Get an array of all known errors
         */
        $errors = Mage::getConfig()->getNode('tig/errors')->asArray();

        /**
         * Loop through each error and compare it's message
         */
        foreach ($errors as $code => $error) {
            $errorMessage = (string)$error['message'];

            /**
             * If a the error's message and the specified message match, return the error code
             */
            if (strcasecmp($message, $errorMessage) === 0) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @param string                          $barcode
     * @param DMP_PostNL_Model_Shipment $postNLShipment
     *
     * @return bool
     * @throws DMP_PostNL_Exception
     */
    public function sendBarcodeEmail($barcode = '', $postNLShipment)
    {
        $barcode = $barcode[0];
        if (empty($barcode)) {
            return false;
        }

        if (!$postNLShipment instanceof DMP_PostNL_Model_Shipment) {
            return false;
        }

        $order = $postNLShipment->getOrder();
        $storeId = $order->getStoreId();
        $templateId = $this->getConfig('tracktrace_template', 'general', $storeId);

        //if no template is set, return false: tracktrace should be send by PostNL
        if ($templateId === null || $templateId == 'dmp_postnl_general_tracktrace_template') {
            return false;
        }

        $retourLabelUrl = '';
        $emailTemplate = Mage::getModel('core/email_template')->load($templateId);
        if (strpos($emailTemplate->getTemplateText(), 'retourlabel_url') > 0) {

            /**
             * @var DMP_PostNL_Model_Api_PostNL $api
             */
            $api = $postNLShipment->getApi();
            $response = $api->createRetourlinkRequest($postNLShipment->getConsignmentId())
                ->setStoreId($postNLShipment->getShipment()->getOrder()->getStoreId())
                ->sendRequest()
                ->getRequestResponse();
            $aResponse = json_decode($response, true);
            if ($aResponse) {
                $retourLabelUrl = $aResponse['data']['download_url'][0]['link'];
            }

        }

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            $paymentBlockHtml = '';
        }

        $shippingAddress = $postNLShipment->getShippingAddress();
        $barcodeUrl = $this->getBarcodeUrl($barcode, $shippingAddress);

        // Set pakjegemak
        foreach ($order->getAddressesCollection() as $address) {
            if ($address->getAddressType() == 'pakje_gemak' && !$address->isDeleted()) {
                $postNLShipment->setShippingAddress($address);
                $order->setShippingAddress($address);
            }
        }

        $templateVariables = array(
            'tracktrace_url' => $barcodeUrl,
            'order' => $order,
            'shipment' => $postNLShipment->getShipment(),
            'retourlabel_url' => $retourLabelUrl,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $paymentBlockHtml,
        );

        try {
            /* @var Mage_Core_Model_Email_Template_Mailer $mailer */
            $mailer = Mage::getModel('core/email_template_mailer');
            $emailInfo = Mage::getModel('core/email_info');
            $emailInfo->addTo($order->getCustomerEmail(), $shippingAddress->getName());

            $mailer->addEmailInfo($emailInfo);

            // Set all required params and send emails.
            $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
            $mailer->setStoreId($storeId);
            $mailer->setTemplateId($templateId);
            $mailer->setTemplateParams($templateVariables);

            $mailer->send();
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }

        return true;
    }

    /**
     * @param $shipmentId
     *
     * @return bool
     */
    public function hasPostNLShipment($shipmentId)
    {
        $postNLShipment = Mage::getModel('dmp_postnl/shipment')->load($shipmentId, 'shipment_id');

        if ($postNLShipment->getId() != 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $shippingMethod
     * @param $setting
     *
     * @return bool
     */
    public function getShippingMethodConfig($shippingMethod, $setting)
    {
        $aConfig = Mage::getStoreConfig('dmp_postnl/' . $shippingMethod, Mage::app()->getStore());

        if ($aConfig[$shippingMethod . '_' . $setting] != false) {
            return $aConfig[$shippingMethod . '_' . $setting];
        } else {
            return false;
        }
    }

    /**
     * Get the price of the chosen options in the checkout
     *
     * @param        $method
     *
     * @param double $price
     *
     * @return float
     */
    public function getExtraPrice($method, $price)
    {
        $onlyRecipientFee = (float)$this->getConfig('only_recipient_fee', 'delivery');
        $signatureFee = (float)$this->getConfig('signature_fee', 'delivery');
        $morningFee = (float)$this->getConfig('morning_delivery_fee', 'morning_delivery');
        $eveningFee = (float)$this->getConfig('eveningdelivery_fee', 'eveningdelivery');
        $signatureAndOnlyRecipient = (float)$this->getConfig('signature_and_only_recipient_fee', 'delivery');
        $pickupFee = (float)$this->getConfig('pickup_fee', 'pickup');
        $pickupExpressFee = (float)$this->getConfig('pickup_express_fee', 'pickup_express');
        $mailboxFee = (float)$this->getConfig('mailbox_fee', 'mailbox');

        switch ($method) {
            case ('delivery_signature'):
                $price += $signatureFee;
                break;
            case ('delivery_only_recipient'):
                $price += $onlyRecipientFee;
                break;
            case ('delivery_signature_and_only_recipient_fee'):
                $price += $signatureAndOnlyRecipient;
                break;
            case ('morning'):
                $price += $morningFee;
                break;
            case ('morning_signature'):
                $price += $morningFee;
                $price += $signatureFee;
                break;
            case ('evening'):
                $price += $eveningFee;
                break;
            case ('evening_signature'):
                $price += $eveningFee;
                $price += $signatureFee;
                break;
            case ('pickup'):
                $price += $pickupFee;
                break;
            case ('pickup_express'):
                $price += $pickupExpressFee;
                break;
            case ('mailbox'):
                $price = $mailboxFee;
                break;
        }

        // Price can't be below 0 euro
        $price = $price > 0 ? $price : 0;

        return $price;
    }

    /**
     * Get drop off day
     *
     * @param $dateTime int
     *
     * @return int
     */
    public function getDropOffDay($dateTime)
    {
        $weekDay = date('N', $dateTime);

        switch ($weekDay) {
            case (1): // Monday
                $dropOff = strtotime("-2 day", $dateTime);
                break;
            case (2):
            case (3):
            case (4):
            case (5): // Friday
            case (6): // Saturday
            case (7): // Sunday
            default:
                $dropOff = strtotime("-1 day", $dateTime);
                break;
        }

        return $dropOff;
    }

    /**
     * Get all countries that support ERS
     *
     * @return array
     */
    public function getReturnCountries()
    {
        return array(
            'NL',
            'DE',
            'EE',
            'FI',
            'FR',
            'GR',
            'GB',
            'IT',
            'LU',
            'MT',
            'AT',
            'SI',
            'SK',
            'ES',
            'CZ',
            'IE',
        );
    }


    /**
     * @return bool
     */
    private function _isFree()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        foreach ($quote->getItemsCollection() as $item) {
            if ($item->getData('free_shipping') == '1') {
                return true;
            }
        }
        return false;
    }
}
