<?php

class ControllerPaymentPaynetEasyForm extends Controller
{
    /**
     * Display "Continue" button on checkout form
     */
    public function index()
    {
        $this->language->load('payment/payneteasy_form');

        $this->data['button_continue'] = $this->language->get('button_continue');
        $this->data['button_continue_action'] = $this->url->link('payment/payneteasy_form/start_payment', '', 'SSL');

        $this->template = $this->getCheckoutTemplatePath();

        $this->render();
    }

    /**
     * Get template path for continue button on checkout form
     *
     * @return      string      Template path
     */
    protected function getCheckoutTemplatePath()
    {
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payneteasy_form.tpl'))
        {
            return $this->config->get('config_template') . '/template/payment/payneteasy_form.php';
        }
        else
        {
            return 'default/template/payment/payneteasy_form.php';
        }
    }
}