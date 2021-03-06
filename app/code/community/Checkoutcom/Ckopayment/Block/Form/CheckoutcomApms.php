<?php

/**
 * Class Checkoutcom_Ckopayment_Block_Form_CheckoutcomApms
 */
class Checkoutcom_Ckopayment_Block_Form_CheckoutcomApms extends Mage_Payment_Block_Form_Cc
{
    const CONFIG = 'ckopayment/checkoutcomConfig';
    const TEMPLATE = 'checkoutcom/form/checkoutcomapms.phtml';

    /**
     * Set template for checkout page
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate(self::TEMPLATE);
    }

    /**
     * Get Magento model for the config
     *
     * @return mixed
     */
    public function _getConfigModel()
    {
        return Mage::getModel(self::CONFIG);
    }

    /**
     * Get alternative payment method selected from admin module setting
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getAlternativePaymentMethods()
    {
        $apmArray = array();
        $apmSelect = $this->_getConfigModel()->getAlternativePaymentMethods();
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($apmSelect !== 0) {
            $apmArr = explode(',', $apmSelect);

            foreach ($apmArr as $value) {
                if ($value == 'ideal' && $currencyCode == 'EUR') {
                    array_push($apmArray, $value);
                }

                if ($value == 'sofort' && $currencyCode == 'EUR') {
                    array_push($apmArray, $value);
                }

                if ($value == 'boleto' ) {
                    if ($currencyCode == 'BRL' || $currencyCode == 'USD' ) {
                        array_push($apmArray, $value);
                    }
                }

                if ($value == 'giropay' && $currencyCode == 'EUR') {
                    array_push($apmArray, $value);
                }

                if ($value == 'poli') {
                    if ($currencyCode == 'AUD' || $currencyCode == 'NZD') {
                        array_push($apmArray, $value);
                    }
                }

                if ($value == 'klarna') {
                    if ($currencyCode == 'EUR'
                        || $currencyCode == 'DKK'
                        || $currencyCode == 'GBP'
                        || $currencyCode == 'NOR'
                        || $currencyCode == 'SEK')
                    {
                        array_push($apmArray, $value);
                    }
                }

                if ($value == 'sepa' && $currencyCode == 'EUR') {
                    array_push($apmArray, $value);
                }

                if ($value == 'eps' && $currencyCode == 'EUR') {
                    array_push($apmArray, $value);
                }

                if ($value == 'bancontact' && $currencyCode == 'EUR') {
                    array_push($apmArray, $value);
                }

                if ($value == 'knet' && $currencyCode == 'KWD') {
                    array_push($apmArray, $value);
                }

                if ($value == 'fawry' && $currencyCode == 'EGP') {
                    array_push($apmArray, $value);
                }

                if ($value == 'alipay' && $currencyCode == 'USD') {
                    array_push($apmArray, $value);
                }
            }
        }

        return $apmArray;
    }

    /**
     * Get iDeal banks from the legacy API
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getIdealBankInfo()
    {
        $secretKey = $this->_getConfigModel()->getSecretKey();
        $environment = $this->_getConfigModel()->getEnvironment();
        $url = "https://api.sandbox.checkout.com/ideal-external/issuers";

        if ($environment == 'live') {
            $url = "https://api.checkout.com/ideal-external/issuers";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: " . $secretKey,
                "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $errorMessage = "No BIC found";

            // Log error in var/log/checkoutcomapms.log
            Mage::log($errorMessage, null, 'checkoutcomapms.log');
            Mage::log($err, Zend_Log::DEBUG, 'checkoutcomapms.log', true);

            //Throw exception and stop order process
            Mage::throwException($errorMessage);
        } else {
            $response = json_decode($response);
            $result = $response->countries;

            foreach ((array) $result as $value) {
                return $value->issuers;
            }
        }
    }

    /**
     * Return Giropay bank info
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getGiropayBankInfo()
    {
        $secretKey = $this->_getConfigModel()->getSecretKey();
        $environment = $this->_getConfigModel()->getEnvironment();
        $url = "https://api.sandbox.checkout.com/giropay/banks";

        if ($environment == 'live') {
            $url = "https://api.checkout.com/giropay/banks";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: " . $secretKey,
                "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $errorMessage = "No bank found";

            // Log error in var/log/checkoutcomapms.log
            Mage::log($errorMessage, null, 'checkoutcomapms.log');
            Mage::log($err, Zend_Log::DEBUG, 'checkoutcomapms.log', true);

            //Throw exception and stop order process
            Mage::throwException($errorMessage);
        } else {
            $result = json_decode($response);

            return $result->banks;
        }
    }

    /**
     * Return klarna session details
     *
     * @return array|mixed|null|object
     * @throws Mage_Core_Exception
     */
    public function createKlarnaSession(){
        $publicKey = $this->_getConfigModel()->getPublicKey();
        $environment = $this->_getConfigModel()->getEnvironment();

        if ($environment == 'sandbox') {
            $creditSessionUrl = 'https://api.sandbox.checkout.com/klarna-external/credit-sessions';
        } else {
            $creditSessionUrl = 'https://api.checkout.com/klarna-external/credit-sessions';
        }

        $cartInfo = $this->getCartInfo();

        $data = array(
            "billing_address" => $cartInfo['billing_address'],
            "purchase_country" => $cartInfo['purchase_country'],
            "currency" => $cartInfo['purchase_currency'],
            "locale" => $cartInfo['locale'],
            "amount" => $cartInfo['order_amount'],
            "tax_amount" => 0,
            "products" => $cartInfo['order_lines'],
        );

        // curl to create klarna session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$creditSessionUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: '.$publicKey,
            'Content-Type:application/json;charset=UTF-8')
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $server_output = curl_exec($ch);
        $err = curl_error($ch);

        curl_close ($ch);

        if ($err) {
            $errorMessage = "Error creating Klarna session";

            // Log error in var/log/checkoutcomapms.log
            Mage::log($errorMessage, null, 'checkoutcomapms.log');
            Mage::log($err, Zend_Log::DEBUG, 'checkoutcomapms.log', true);

            //Throw exception and stop order process
            Mage::throwException($errorMessage);
        } else {
            $response = json_decode($server_output);

            return $response;
        }
    }

    /**
     * Return cart info
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getCartInfo()
    {
        $quote = Mage::getModel('ckopayment/checkoutcomUtils')->getQuote(null);
        $billingAddress = $quote->getBillingAddress();
        // Get billing Street from billing address
        $billStreet = Mage::helper('customer/address')
            ->convertStreetLines($billingAddress->getStreet(), 2);

        // Get Shipping address from quote
        $shippingAddress = $quote->getShippingAddress();
        // Get Shipping Street from billing address
        $shipStreet = Mage::helper('customer/address')
            ->convertStreetLines($shippingAddress->getStreet(), 2);

        $phoneNumber = $shippingAddress->getTelephone();

        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $localisation = Mage::app()->getLocale()->getLocaleCode();
        $locale = str_replace("_","-",$localisation);
        $quoteData = $quote->getData();
        $grandTotal = $quoteData['grand_total'];

        $amountCent = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($grandTotal, $currencyCode);

        $items =Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
        $products = array();

        foreach($items as $item) {
            $unitPrice = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($item->getPrice(), $currencyCode);
            $products[] = array(
                "name" => $item->getName(),
                "quantity" => $item->getQty(),
                "unit_price" => $unitPrice,
                "tax_rate" => 0,
                "total_amount" => $unitPrice * $item->getQty(),
                "total_tax_amount" => 0,
                "type" => "physical",
                "reference" => $item->getName(),
                "total_discount_amount" => 0

            );
        }

        $shippingMethod = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingDescription();
        $shippingMethodCode = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod();
        $shippingAmount = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingAmount();
        $shippingAmountCents = Mage::getModel('ckopayment/checkoutcomUtils')->valueToDecimal($shippingAmount, $currencyCode);
        // Set shipping method in product to calculate total amount correctly.
        $products[] = array(
            "name" => $shippingMethod,
            "quantity" => 1,
            "unit_price" => $shippingAmountCents,
            "tax_rate" => 0,
            "total_amount" => $shippingAmountCents,
            "total_tax_amount" => 0,
            "type" => "shipping_fee",
            "reference" => $shippingMethodCode,
            "total_discount_amount" => 0
        );

        $cartInfo = array(
            "purchase_country" =>$billingAddress->getCountry(),
            "purchase_currency" => $currencyCode,
            "locale" => 'en-GB', //$locale,
            "billing_address" => array(
                "given_name" => $billingAddress->getFirstname(),
                "family_name" => $billingAddress->getLastname(),
                "email" => Mage::helper('ckopayment')->getCustomerEmail(null),
                "street_address" => $billStreet[0],
                "street_address2" => $billStreet[1],
                "postal_code" => $billingAddress->getPostcode(),
                "city" => $billingAddress->getCity(),
                "region" => $billingAddress->getCity(),
                "phone" => $phoneNumber,
                "country" => $billingAddress->getCountry(),
            ),
            "order_amount" => $amountCent,
            "order_tax_amount" => 0,
            "order_lines" => $products
        );

        return $cartInfo;
    }
}
