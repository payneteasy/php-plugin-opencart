<?php

class ControllerPaymentPaynetEasyForm extends Controller
{
    /**
     * Validation errors
     *
     * @var array
     */
    protected $error = array();

    /**
     * Show module edit config page
     */
    public function index()
    {
		$this->load->language('payment/payneteasy_form');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
        $this->load->model('setting/extension');
//        $this->load->model('payment/payneteasy_form');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
        {
            unset($this->request->post['payneteasy_form_module']);

			$this->model_setting_setting->editSetting('payneteasy_form', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->getSecureLink('extension/payment'));
		}
        else
        {
            $this->setConfigPhrases();
            $this->setConfigValues();
            $this->setConfigBreadcrumbs();

            $this->data['error'] = $this->error;

            //button actions
            $this->data['action']   = $this->getSecureLink('payment/payneteasy_form');
            $this->data['cancel']   = $this->getSecureLink('extension/payment');
            $this->data['token']    = $this->session->data['token'];

            $this->template = 'payment/payneteasy_form.php';
            $this->children = array
            (
                'common/header',
                'common/footer'
            );

            $this->response->setOutput($this->render());
        }
    }

    /**
     * Validates module config
     */
    public function validate()
    {
		if (!$this->user->hasPermission('modify', 'payment/payneteasy_form'))
        {
			$this->error['warning'] = $this->language->get('error_permission');
		}

        $this->assertNotEmpty('checkout_title');
        $this->assertNotEmpty('end_point');
        $this->assertNotEmpty('login');
        $this->assertNotEmpty('signing_key');
        $this->assertNotEmpty('sandbox_gateway');
        $this->assertNotEmpty('production_gateway');
        $this->assertNotEmpty('order_success_status');
        $this->assertNotEmpty('order_failure_status');

        return empty($this->error);
    }

    /**
     * Set template breadcrubms for edit config page
     */
    protected function setConfigBreadcrumbs()
    {
        $this->setTemplateBreadcrumb('text_home',       'common/home', false);
        $this->setTemplateBreadcrumb('text_payment',    'extension/payment');
        $this->setTemplateBreadcrumb('heading_title',   'payment/payneteasy_form');
    }

    /**
     * Set template phrases for edit config page
     */
    protected function setConfigPhrases()
    {
        $this->setTemplatePhrase('heading_title');

        $this->setTemplatePhrase('text_enabled');
        $this->setTemplatePhrase('text_disabled');

        $this->setTemplatePhrase('button_save');
        $this->setTemplatePhrase('button_cancel');

        $this->setTemplatePhrase('entry_checkout_title');
        $this->setTemplatePhrase('entry_end_point');
        $this->setTemplatePhrase('entry_login');
        $this->setTemplatePhrase('entry_signing_key');
        $this->setTemplatePhrase('entry_sandbox_gateway');
        $this->setTemplatePhrase('entry_production_gateway');
        $this->setTemplatePhrase('entry_gateway_mode');
        $this->setTemplatePhrase('entry_order_success_status');
        $this->setTemplatePhrase('entry_order_failure_status');
        $this->setTemplatePhrase('entry_status');
        $this->setTemplatePhrase('entry_sort_order');
    }

    /**
     * Set template form values for edit config page
     */
    protected function setConfigValues()
    {
        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->setConfigValue('checkout_title');
        $this->setConfigValue('end_point');
        $this->setConfigValue('login');
        $this->setConfigValue('signing_key');
        $this->setConfigValue('sandbox_gateway');
        $this->setConfigValue('production_gateway');
        $this->setConfigValue('gateway_mode');
        $this->setConfigValue('order_success_status');
        $this->setConfigValue('order_failure_status');
        $this->setConfigValue('status');
        $this->setConfigValue('sort_order');
    }

    /**
     * Set view template phrase
     *
     * @param       string      $phrase_key     Phrase key
     */
    protected function setTemplatePhrase($phrase_key)
    {
		$this->data[$phrase_key] = $this->language->get($phrase_key);
    }

    /**
     * Set template form value for edit config page
     *
     * @param       string      $key        Config key
     */
    protected function setConfigValue($key)
    {
        $full_key = "payneteasy_form_{$key}";

        if (isset($this->request->post[$full_key]))
        {
			$this->data[$full_key] = $this->request->post[$full_key];
		}
        else
        {
			$this->data[$full_key] = $this->config->get($full_key);
		}
    }


    /**
     * Set template breadcrubm
     *
     * @param       string      $phrase_key     Breadcrumb phrase key
     * @param       string      $route          Breadcrumb route
     * @param       string      $separator      Breadcrumbs separator
     */
    protected function setTemplateBreadcrumb($phrase_key, $route, $separator = ' :: ')
    {
        if (!isset($this->data['breadcrumbs']) || !is_array($this->data['breadcrumbs']))
        {
            $this->data['breadcrumbs'] = array();
        }

   		$this->data['breadcrumbs'][] = array
        (
       		'text'      => $this->language->get($phrase_key),
			'href'      => $this->getSecureLink($route),
      		'separator' => $separator
   		);
    }

    /**
     * Get secure (with token and https) link
     *
     * @param       string      $route      Route
     *
     * @return      string                  URL
     */
    protected function getSecureLink($route)
    {
        return $this->url->link($route, 'token=' . $this->session->data['token'], 'SSL');
    }

    /**
     * Assert, that request key is not empty.
     * If request key is empty, error message for that key will be assigned.
     *
     * @param       string      $key        Request key
     */
    protected function assertNotEmpty($key)
    {
        $full_key   = "payneteasy_form_{$key}";
        $error_key  = "error_{$full_key}";

		if (empty($this->request->post[$full_key]))
        {
			$this->error[$full_key] = $this->language->get($error_key);
		}
    }
}