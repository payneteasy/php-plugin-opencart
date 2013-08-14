<?php

class ControllerPaymentPaynetEasyForm extends Controller
{
    /**
     * Display "Continue" button on checkout form
     */
    public function index()
    {
        $this->language->load('payment/payneteasy_form');

        $this->data['button_continue']          = $this->language->get('button_continue');
        $this->data['button_continue_action']   = $this->getSecureLink('payment/payneteasy_form/start');

        $this->template = $this->getTemplatePath('payneteasy_form_checkout.php');

        $this->render();
    }

    /**
     * Start order processing and redirect to PaynetEasy
     */
    public function start()
    {
        if (!$this->canStart())
        {
            $this->redirect($this->getSecureLink('checkout/cart'));
        }

        $this->load->model('payment/payneteasy_form');

        $order_id       = $this->session->data['order_id'];
        $redirect_url   = $this->getSecureLink('payment/payneteasy_form/finish', "order_id={$order_id}", 'SSL');

        try
        {
            $response = $this
                ->model_payment_payneteasy_form
                ->startSale($order_id, $redirect_url)
            ;

            $this->redirect($response->getRedirectUrl());
        }
        catch (Exception $exception)
        {
            $this->logException($exception);
            $this->errorRedirect('text_technical_error');
        }
    }

    public function finish()
    {
        ;
    }

    /**
     * Display page with error message if payment failed
     */
    public function error()
    {
        $this->language->load('payment/payneteasy_form');
        $this->document->setTitle($this->language->get('error_heading_title'));

        $this->data['heading_title']            = $this->language->get('heading_title');
        $this->data['text_payment_failed']      = $this->language->get('text_payment_failed');
        $this->data['text_payment_fail_reason'] = $this->session->data['payment_fail_reason'];

        $this->data['button_continue']          = $this->language->get('button_continue');
        $this->data['continue']                 = $this->getSecureLink('checkout/checkout');

        $this->setErrorBreadcrumbs();

        $this->template = $this->getTemplatePath('payneteasy_form_error.php');

        $this->children = array
        (
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        $this->response->setOutput($this->render());
    }

    /**
     * Redirect to page with error message
     *
     * @param       strign      $message_key        Error message language key
     */
    protected function errorRedirect($message_key)
    {
        $this->session->data['payment_fail_reason'] = $this->language->get($message_key);
        $this->redirect($this->getSecureLink('payment/payneteasy_form/error'));
    }

    /**
     * Get template path for continue button on checkout form
     *
     * @return      string      Template path
     */
    protected function getTemplatePath($template_name)
    {
        $custom_path = "{$this->config->get('config_template')}/template/payment/{$template_name}";

        if (file_exists(DIR_TEMPLATE . $custom_path))
        {
            return $custom_path;
        }
        else
        {
            return "default/template/payment/{$template_name}";
        }
    }

    /**
     * True, if context valid for start payment, false otherwise
     *
     * @return      boolean
     */
    protected function canStart()
    {
        if (    (!$this->cart->hasProducts() &&  empty($this->session->data['vouchers']))
            ||  (!$this->cart->hasStock()    && !$this->config->get('config_stock_checkout')))
        {
            return false;
        }

        // If the customer is not logged in
        // and guest checkout disabled
        // or login is required before price
        // or order has downloads
        if (    !$this->customer->isLogged()
            && (   !$this->config->get('config_guest_checkout')
                ||  $this->config->get('config_customer_price')
                ||  $this->cart->hasDownload()
                ||  $this->cart->hasRecurringProducts())
           )
        {
            return false;
        }

        return true;
    }

    /**
     * Set breadcrumbs for error page
     */
    protected function setErrorBreadcrumbs()
    {
        $this->language->load('checkout/checkout');

        $this->setTemplateBreadcrumb('text_home',     'common/home', false);
        $this->setTemplateBreadcrumb('text_cart',     'checkout/cart');
        $this->setTemplateBreadcrumb('heading_title', 'checkout/checkout');

        $this->language->load('payment/payneteasy_form');

        $this->setTemplateBreadcrumb('error_heading_title', 'payment/payneteasy_form/error');
    }

    /**
     * Set template breadcrubm
     *
     * @param       string      $phrase_key         Breadcrumb phrase key
     * @param       string      $route              Breadcrumb route
     * @param       boolean     $add_separator      Add breadcrumbs separator or not
     */
    protected function setTemplateBreadcrumb($phrase_key, $route, $add_separator = true)
    {
        if (!isset($this->data['breadcrumbs']) || !is_array($this->data['breadcrumbs']))
        {
            $this->data['breadcrumbs'] = array();
        }

   		$this->data['breadcrumbs'][] = array
        (
       		'text'      => $this->language->get($phrase_key),
			'href'      => $this->getSecureLink($route),
      		'separator' => $add_separator ? $this->language->get('text_separator') : false
   		);
    }

    /**
     * Log exception
     *
     * @param       Exception       $exception      Exception to log
     */
    protected function logException(Exception $exception)
    {
        $this->log->write((string) $exception);
    }

    /**
     * Get secure (with https) link
     *
     * @param       string      $route      Route
     * @param       string      $params     Route parameters
     *
     * @return      string                  URL
     */
    protected function getSecureLink($route, $params = '')
    {
        return $this->url->link($route, $params, 'SSL');
    }
}