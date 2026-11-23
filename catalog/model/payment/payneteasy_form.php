<?php

require_once DIR_SYSTEM . '../vendor/autoload.php';

use PaynetEasy\PaynetEasyApi\PaymentData\PaymentTransaction;
use PaynetEasy\PaynetEasyApi\PaymentData\Payment;
use PaynetEasy\PaynetEasyApi\PaymentData\Customer;
use PaynetEasy\PaynetEasyApi\PaymentData\BillingAddress;

use PaynetEasy\PaynetEasyApi\Util\Validator;
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

        $this->assertOwnOrder($opencart_order, $order_id);

        // A payment is started only for an order without a payment result
        if (!$this->isAwaitingPayment($opencart_order))
        {
            throw new ResponseException("Order with id '" . (int) $order_id . "' already has a payment result");
        }

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
     * @return      CallbackResponse|null           Callback object, null if the order already has a payment result
     */
    public function finishSale($order_id, array $callback_data)
    {
        $this->load->model('checkout/order');

        $opencart_order     = $this->model_checkout_order->getOrder($order_id);

        $this->assertOwnOrder($opencart_order, $order_id);

        // Only an order waiting for payment changes its state,
        // the request data is not used for other orders
        if (!$this->isAwaitingPayment($opencart_order))
        {
            return null;
        }

        $callback_response  = new CallbackResponse($callback_data);

        $paynet_transaction = $this->getPaynetTransaction($opencart_order);
        $paynet_transaction->setStatus(PaymentTransaction::STATUS_PROCESSING);
        $this->loadPaymentIds(
            $paynet_transaction->getPayment(),
            isset($callback_data['orderid']) ? (string) $callback_data['orderid'] : ''
        );

        // Invalid data: the exception is passed on, the order is not changed
        $callback_response = $this
            ->getPaymentProcessor()
            ->processCustomerReturn($callback_response, $paynet_transaction)
        ;

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
     * Checks that the order exists and is paid with this payment method
     *
     * @param       array|false     $opencart_order     OpenCart order
     * @param       mixed           $order_id           Order ID from the request
     *
     * @throws      ResponseException
     */
    protected function assertOwnOrder($opencart_order, $order_id)
    {
        if (    empty($opencart_order)
            ||  !isset($opencart_order['payment_code'])
            ||  $opencart_order['payment_code'] !== 'payneteasy_form')
        {
            throw new ResponseException("Order with id '" . (int) $order_id . "' not found");
        }
    }

    /**
     * True, if the order is paid: it has the success status of this module
     *
     * @param       integer     $order_id       Order ID
     *
     * @return      boolean
     */
    public function isPaidOrder($order_id)
    {
        $this->load->model('checkout/order');

        $opencart_order = $this->model_checkout_order->getOrder($order_id);

        return  !empty($opencart_order)
            &&  $opencart_order['payment_code'] === 'payneteasy_form'
            &&  (int) $opencart_order['order_status_id'] === (int) $this->getModuleConfigValue('order_success_status');
    }

    /**
     * True, if the order has not got a payment result yet
     *
     * @param       array       $opencart_order     OpenCart order
     *
     * @return      boolean
     */
    protected function isAwaitingPayment(array $opencart_order)
    {
        // The order gets its first status only from the payment result
        return (int) $opencart_order['order_status_id'] === 0;
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

        if ((int) $opencart_order['order_status_id'] === 0)
        {
            $this->model_checkout_order->confirm($order_id, $status_id);
        }
        else
        {
            $this->model_checkout_order->update($order_id, $status_id);
        }
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
     * The order may have several payment attempts: the one named
     * by the gateway is taken, if it belongs to this order.
     *
     * @param       Payment         $payment        PaynetEasy payment
     * @param       string          $paynet_id      PaynetEasy payment id from the gateway data
     *
     * @throws      RuntimeException                Can not found paynet_id for payment client_id
     */
    protected function loadPaymentIds(Payment $payment, $paynet_id)
    {
        $client_id = $payment->getClientId();

        $result = $this->db->query
        ("
            SELECT `paynet_id`
            FROM `" . DB_PREFIX . "payneteasy_form_payment`
            WHERE `client_id` = '{$this->db->escape($client_id)}'
              AND `paynet_id` = '{$this->db->escape($paynet_id)}'
        ");

        if ($paynet_id === '' || empty($result->row))
        {
            throw new RuntimeException("Can not find payment '" . (int) $paynet_id . "' for order '" . (int) $client_id . "'");
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
        ;

        // The gateway accepts only US and Canada state codes
        if (Validator::validateByRule($opencart_order['payment_zone_code'], Validator::STATE, false))
        {
            $paynet_address->setState($opencart_order['payment_zone_code']);
        }

        $paynet_customer
            ->setEmail($opencart_order['email'])
            ->setFirstName($opencart_order['firstname'])
            ->setLastName($opencart_order['lastname'])
            ->setIpAddress($opencart_order['ip'])
        ;

        $paynet_payment
            ->setClientId($opencart_order['order_id'])
            ->setDescription($this->getPaynetPaymentDescription($opencart_order))
            ->setAmount($this->getOrderAmount($opencart_order))
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
     * Order total in the order currency.
     * OpenCart stores the total in the default currency.
     *
     * @param       array       $opencart_order     OpenCart order
     *
     * @return      string
     */
    protected function getOrderAmount(array $opencart_order)
    {
        return number_format(
            (float) $this->currency->format(
                $opencart_order['total'],
                $opencart_order['currency_code'],
                $opencart_order['currency_value'],
                false
            ),
            2, '.', ''
        );
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