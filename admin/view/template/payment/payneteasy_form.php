<?php echo $header; ?>

<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb): ?>
            <?php echo $breadcrumb['separator']; ?>
            <a href="<?php echo $breadcrumb['href']; ?>">
                <?php echo $breadcrumb['text']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (isset($error_warning)): ?>
        <div class="warning"><?php echo $error_warning; ?></div>
    <?php endif; ?>

    <div class="box">
        <div class="heading">
            <h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
            <div class="buttons">
                <a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
                <a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
            </div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_end_point; ?>
                        </td>
                        <td>
                            <input type="text" name="end_point" value="<?php echo $end_point; ?>" />
                            <?php if (isset($error_end_point)):?>
                                <span class="error"><?php echo $error_end_point; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_login; ?>
                        </td>
                        <td>
                            <input type="text" name="login" value="<?php echo $login; ?>" />
                            <?php if (isset($error_login)):?>
                                <span class="error"><?php echo $error_login; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_signing_key; ?>
                        </td>
                        <td>
                            <input type="text" name="signing_key" value="<?php echo $signing_key; ?>" />
                            <?php if (isset($error_signing_key)):?>
                                <span class="error"><?php echo $error_signing_key; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_sandbox_gateway; ?>
                        </td>
                        <td>
                            <input type="text" name="sandbox_gateway" value="<?php echo $sandbox_gateway; ?>" />
                            <?php if (isset($error_sandbox_gateway)):?>
                                <span class="error"><?php echo $error_sandbox_gateway; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_production_gateway; ?>
                        </td>
                        <td>
                            <input type="text" name="production_gateway" value="<?php echo $production_gateway; ?>" />
                            <?php if (isset($error_production_gateway)):?>
                                <span class="error"><?php echo $error_production_gateway; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_gateway_mode; ?>
                        </td>
                        <td>
                            <select name="gateway_mode">
                                    <option value="sandbox"
                                        <?php if ($gateway_mode == 'sandbox'): ?>
                                            selected="selected"
                                        <?php endif; ?>
                                    >
                                        Sandbox
                                    </option>
                                    <option value="production"
                                        <?php if ($gateway_mode == 'production'): ?>
                                            selected="selected"
                                        <?php endif; ?>
                                    >
                                        Production
                                    </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_order_success_status; ?>
                        </td>
                        <td>
                            <select name="order_success_status">
                                <?php foreach ($order_statuses as $order_status): ?>
                                    <?php if ($order_status['order_status_id'] == $order_success_status): ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected">
                                            <?php echo $order_status['name']; ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>">
                                            <?php echo $order_status['name']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_order_failure_status; ?>
                        </td>
                        <td>
                            <select name="order_failure_status">
                                <?php foreach ($order_statuses as $order_status): ?>
                                    <?php if ($order_status['order_status_id'] == $order_failure_status): ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected">
                                            <?php echo $order_status['name']; ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="<?php echo $order_status['order_status_id']; ?>">
                                            <?php echo $order_status['name']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="required">*</span> <?php echo $entry_payneteasy_form_status; ?>
                        </td>
                        <td>
                            <select name="payneteasy_form_status">
                                <option value="0"
                                    <?php if ($payneteasy_form_status == '0'): ?>
                                        selected="selected"
                                    <?php endif; ?>
                                >
                                    <?php echo $text_disabled; ?>
                                </option>
                                <option value="1"
                                    <?php if ($payneteasy_form_status == '1'): ?>
                                        selected="selected"
                                    <?php endif; ?>
                                >
                                    <?php echo $text_enabled; ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $entry_payneteasy_form_sort_order; ?>
                        </td>
                        <td>
                            <input type="text" name="sort_order" value="<?php echo $payneteasy_form_sort_order; ?>" size="1" />
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>
<?php echo $footer; ?>