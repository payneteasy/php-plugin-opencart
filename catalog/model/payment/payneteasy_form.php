<?php

require_once DIR_SYSTEM . '../vendor/autoload.php';

use PaynetEasy\PaynetEasyApi\PaymentData\PaymentTransaction;
use PaynetEasy\PaynetEasyApi\PaymentData\Payment;
use PaynetEasy\PaynetEasyApi\PaymentData\Customer;
use PaynetEasy\PaynetEasyApi\PaymentData\BillingAddress;

use PaynetEasy\PaynetEasyApi\Utils\Validator;
use PaynetEasy\PaynetEasyApi\PaymentData\QueryConfig;
use PaynetEasy\PaynetEasyApi\Transport\CallbackResponse;

use PaynetEasy\PaynetEasyApi\PaymentProcessor;
use PaynetEasy\PaynetEasyApi\Exception\ResponseException;

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
     * Method executes query to PaynetEasy gateway and returns response from gateway.
     * After that user must be redirected to the Response::getRedirectUrl()
     *
     * @param       integer                         $order_id               Order ID
     * @param       string                          $redirect_url           Url for final payment processing
     *
     * @return      \PaynetEasy\PaynetEasyApi\Transport\Response            Gateway response object
     */
    public function startSale($order_id, $redirect_url)
    {
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

        $this->savePaymentIds($paynet_transaction->getPayment());

        return $response;
    }

    /**
     * Finish order processing.
     * Method checks callback data and returns object with them.
     * After that order processing result can be displayed.
     *
     * @param       integer     $order_id           Order ID
     * @param       array       $callback_data      Callback data from Paynet
     *
     * @return      CallbackResponse                Callback object
     */
    public function finishSale($order_id, array $callback_data)
    {
        $this->load->model('checkout/order');

        $opencart_order     = $this->model_checkout_order->getOrder($order_id);

        if (empty($opencart_order))
        {
            throw new ResponseException("Order with id '{$order_id}' not found");
        }

        $paynet_transaction = $this->getPaynetTransaction($opencart_order);
        $paynet_transaction->setStatus(PaymentTransaction::STATUS_PROCESSING);
        $this->loadPaymentIds($paynet_transaction->getPayment());

        try
        {
            $callback_response = $this
                ->getPaymentProcessor()
                ->processCustomerReturn(new CallbackResponse($callback_data), $paynet_transaction)
            ;
        }
        catch (Exception $e)
        {
            $this->cancelOrder($opencart_order, "Order '{$order_id}' cancelled, error occured");
            throw $e;
        }

        if ($paynet_transaction->isApproved())
        {
            $this->completeOrder($opencart_order);
        }
        else
        {
            $this->cancelOrder($opencart_order, $paynet_transaction->getLastError()->getMessage());
        }

        return $callback_response;
    }

    /**
     * Set order status to "payment success"
     *
     * @param       array       $opencart_order     OpenCart order
     */
    protected function completeOrder(array $opencart_order)
    {
        $order_id   = $opencart_order['order_id'];
        $status_id  = $this->getModuleConfigValue('order_success_status');

        $this
            ->model_checkout_order
            ->update($order_id, $status_id)
        ;
    }

    /**
     * Set order status to "payment failed"
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
     * Save PaynetEasy payment ids to database.
     * PaynetEasy id and client id will be saved.
     *
     * @param       Payment         $payment        PaynetEasy payment
     */
    protected function savePaymentIds(Payment $payment)
    {
        $this->db->query
        ("
            INSERT INTO `" . DB_PREFIX . "payneteasy_form_payment`
            SET `paynet_id` = '{$this->db->escape($payment->getPaynetId())}',
                `client_id` = '{$this->db->escape($payment->getClientId())}'
        ");
    }

    /**
     * Load PaynetEasy payment paynet_id from database.
     *
     * @param       Payment         $payment        PaynetEasy payment
     *
     * @throws      RuntimeException                Can not found paynet_id for payment client_id
     */
    protected function loadPaymentIds(Payment $payment)
    {
        $client_id = $payment->getClientId();

        $result = $this->db->query
        ("
            SELECT `paynet_id`
            FROM `" . DB_PREFIX . "payneteasy_form_payment`
            WHERE `client_id` = '{$this->db->escape($client_id)}'
        ");

        if (empty($result->row))
        {
            throw new RuntimeException("Can not find 'paynet_id' for Payment with 'client_id' = {$client_id}");
        }

        $payment->setPaynetId($result->row['paynet_id']);
    }

    /**
     * Get PaynetEasy payment transaction object by OpenCart order data array
     *
     * @param       MageOrder       $mageOrder          Magento order
     * @param       string          $redirectUrl        Url for final payment processing
     *
     * @return      PaynetTransaction                   PaynetEasy payment transaction
     */
    protected function getPaynetTransaction(array $opencart_order, $redirect_url = null)
    {
        $query_config        = new QueryConfig;
        $paynet_address      = new BillingAddress;
        $paynet_transaction  = new PaymentTransaction;
        $paynet_payment      = new Payment;
        $paynet_customer     = new Customer;

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
     * Get PaynetEasy order description by opencart order
     *
     * @param       array       $opencart_order     Magento order
     *
     * @return      string                          PaynetEasy order description
     */
    protected function getPaynetPaymentDescription(array $opencart_order)
    {
        $this->language->load('payment/payneteasy_form');

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