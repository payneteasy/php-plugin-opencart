<?php

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
    public function getMethod(array $address, $total)
    {
        return array
        (
            'code'       => 'payneteasy_form',
            'title'      => $this->getConfigValue('checkout_title'),
            'sort_order' => $this->getConfigValue('sort_order')
        );
    }

    /**
     * Get module config value
     *
     * @param       string      $key        Config value key
     *
     * @return      mixed                   Config value
     */
    protected function getConfigValue($key)
    {
        $full_key = "payneteasy_form_{$key}";
        return $this->config->get($full_key);
    }
}