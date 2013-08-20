<?php

class ModelPaymentPayneteasyForm extends Model
{
    /**
     * Create table for PaynetEasy payment ids
     */
    public function install()
    {
        $this->db->query
        ("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payneteasy_form_payment`
            (
                `paynet_id` VARCHAR(50) NOT NULL,
                `client_id` VARCHAR(50) NOT NULL,
                PRIMARY KEY (`paynet_id`)
            );
        ");
    }

    /**
     * Delete table for PaynetEasy payment ids
     */
    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payneteasy_form_payment`;");
    }
}