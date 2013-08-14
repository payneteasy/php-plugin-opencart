<?php

require_once DIR_SYSTEM . '../vendor/autoload.php';

use PaynetEasy\PaynetEasyApi\PaymentData\PaymentTransaction as PaynetTransaction;
use PaynetEasy\PaynetEasyApi\PaymentData\Payment            as PaynetPayment;
use PaynetEasy\PaynetEasyApi\PaymentData\Customer           as PaynetCustomer;
use PaynetEasy\PaynetEasyApi\PaymentData\BillingAddress     as PaynetAddress;

use PaynetEasy\PaynetEasyApi\Utils\Validator;
use PaynetEasy\PaynetEasyApi\PaymentData\QueryConfig;

use PaynetEasy\PaynetEasyApi\PaymentProcessor;

class ModelPaymentPayneteasyForm extends Model
{
    /**
     * Get payment method metadata
     *
     * @param       array       $address        Billing address
     * @param       float       $total          Order total
     *
     * @return      array
     */
    public function getMethod()
    {
        return array
        (
            'code'       => 'payneteasy_form',
            'title'      => $this->getModuleConfigValue('checkout_title'),
            'sort_order' => $this->getModuleConfigValue('sort_order')
        );
    }

    /**
     * Starts order processing.
     * Method executes query to paynet gateway and returns response from gateway.
     * After that user must be redirected to the Response::getRedirectUrl()
     *
     * @param       integer                         $order_id               Order ID
     * @param       string                          $redirect_url           Url for final payment processing
     *
     * @return      \PaynetEasy\PaynetEasyApi\Transport\Response            Gateway response object
     */
    public function startSale($order_id, $redirect_url)
    {
        $this->language->load('payment/payneteasy_form');
        $this->load->model('checkout/order');

        $opencart_order     = $this->model_checkout_order->getOrder($order_id);
        $paynet_transaction = $this->getPaynetTransaction($opencart_order, $redirect_url);

        try
        {
            $response = $this
                ->getPaymentProcessor()
                ->executeQuery('sale-form', $paynet_transaction)
            ;
        }
        catch (Exception $e)
        {
            $this->cancelOrder($opencart_order, "Order '{$order_id}' cancelled, error occured");
            throw $e;
        }

        $this->savePaynetPaymentIds($paynet_transaction->getPayment());

        return $response;
    }

    public function finishSale()
    {
        ;
    }

    /**
     * Cancel order
     *
     * @param       array       $opencart_order     OpenCart order
     * @param       string      $message            Error message
     */
    protected function cancelOrder(array $opencart_order, $message)
    {
        $order_id   = $opencart_order['order_id'];
        $status_id  = $this->getModuleConfigValue('order_failure_status');

        $this
            ->model_checkout_order
            ->update($order_id, $status_id, $message)
        ;
    }

    /**
     * Save paynet payment ids to database.
     * Paynet id and client id will be saved.
     *
     * @param       Payment         $payment        Paynet payment
     */
    protected function savePaynetPaymentIds(Payment $payment)
    {
        $this->db->query
        ("
            INSERT INTO `" . DB_PREFIX . "payneteasy_form_payment`
            SET `paynet_id` = '{$this->db->escape($payment->getPaynetId())}',
                `client_id` = '{$this->db->escape($payment->getClientId())}'
        ");
    }

    /**
     * Get Paynet payment transaction object by OpenCart order data array
     *
     * @param       MageOrder       $mageOrder          Magento order
     * @param       string          $redirectUrl        Url for final payment processing
     *
     * @return      PaynetTransaction                   Paynet payment transaction
     */
    protected function getPaynetTransaction(array $opencart_order, $redirect_url = null)
    {
        $query_config        = new QueryConfig;
        $paynet_address      = new PaynetAddress;
        $paynet_transaction  = new PaynetTransaction;
        $paynet_payment      = new PaynetPayment;
        $paynet_customer     = new PaynetCustomer;

        $paynet_address
            ->setCountry($opencart_order['payment_iso_code_2'])
            ->setCity($opencart_order['payment_city'])
            ->setFirstLine($opencart_order['payment_address_1'])
            ->setZipCode($opencart_order['payment_postcode'])
            ->setPhone($opencart_order['telephone'])
            ->setState($opencart_order['payment_zone_code'])
        ;

        $paynet_customer
            ->setEmail($opencart_order['email'])
            ->setFirstName($opencart_order['firstname'])
            ->setLastName($opencart_order['lastname'])
            ->setIpAddress($opencart_order['ip'])
        ;

        $paynet_payment
            ->setClientId($opencart_order['order_id'])
            ->setDescription($this->getPaynetPaymentDescription($opencart_order))
            ->setAmount($opencart_order['total'])
            ->setCurrency($opencart_order['currency_code'])
            ->setCustomer($paynet_customer)
            ->setBillingAddress($paynet_address)
        ;

        $query_config
            ->setEndPoint($this->getModuleConfigValue('end_point'))
            ->setLogin($this->getModuleConfigValue('login'))
            ->setSigningKey($this->getModuleConfigValue('signing_key'))
            ->setGatewayMode($this->getModuleConfigValue('gateway_mode'))
            ->setGatewayUrlSandbox($this->getModuleConfigValue('sandbox_gateway'))
            ->setGatewayUrlProduction($this->getModuleConfigValue('production_gateway'))
        ;

        if (Validator::validateByRule($redirect_url, Validator::URL, false))
        {
            $query_config
                ->setRedirectUrl($redirect_url)
                ->setCallbackUrl($redirect_url)
            ;
        }

        $paynet_transaction
            ->setPayment($paynet_payment)
            ->setQueryConfig($query_config)
        ;

        return $paynet_transaction;
    }

    /**
     * Get paynet order description by opencart order
     *
     * @param       array       $opencart_order     Magento order
     *
     * @return      string                          Paynet order description
     */
    protected function getPaynetPaymentDescription(array $opencart_order)
    {
        return  "{$this->language->get('shopping_in')} {$this->getConfigValue('config_name')}; " .
                "{$this->language->get('order_id')}: {$opencart_order['order_id']}";
    }

    /**
     * Get PaymentProcessor with lazy load
     *
     * @return      PaymentProcessor
     */
    protected function getPaymentProcessor()
    {
        static $payment_processor = null;

        if (empty($payment_processor))
        {
            $payment_processor = new PaymentProcessor;
        }

        return $payment_processor;
    }

    /**
     * Get module config value
     *
     * @param       string      $key        Config value key
     *
     * @return      mixed                   Config value
     */
    protected function getModuleConfigValue($key)
    {
        return $this->getConfigValue("payneteasy_form_{$key}");
    }

    /**
     * Get any config value
     *
     * @param       string      $key        Config value key
     *
     * @return      mixed                   Config value
     */
    protected function getConfigValue($key)
    {
        return $this->config->get($key);
    }
}