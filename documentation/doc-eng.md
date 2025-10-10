# PaynetEasy Payment Plugin for OpenCart 1.x

# 1. [Requirements](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#1-requirements-1)
# 2. [Functionality](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#2-functionality-1)
# 3. [Package Build](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#3-package-build-1)
# 4. [Plugin Installation](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#4-installation-1)
# 5. [Plugin Configuration](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#5-plugin-configuration-1)
# 6. [Plugin Uninstallation](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#6-plugin-uninstallation-1)
# 7. [User Registration](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#7-user-registration-1)
# 8. [Payment Flow](https://github.com/annihilatoratm/opencart-doc/blob/main/documentation/doc-eng.md#8-payment-flow-1)

## 1. Requirements

* PHP version: 5.3 – 5.5  
* [Curl extension](http://php.net/manual/en/book.curl.php).  
* [OpenCart](http://www.opencart.com/index.php?route=download/download) 1.x (the plugin was tested with version 1.5).  

## 2. Functionality

- [Payment Form Integration](https://doc.payneteasy.eu/integration/api_use_cases/sale_form.html)

## 3. Package Build

3.1. [Install the composer](http://getcomposer.org/doc/00-intro.md).  
3.2. Clone the plugin repository: `composer create-project payneteasy/php-plugin-opencart --stability=dev --prefer-dist`.  
3.3. Navigate to the plugin directory: `cd php-plugin-opencart`.  
3.4. Build the plugin archive: `composer archive --format=zip`.  

## 4. Plugin Installation
 
4.1. [Download the plugin package](#get_package).  
4.2. Extract the contents of the archive into your OpenCart root directory.  

## 5. Plugin Configuration

5.1. Log in to the OpenCart Admin Panel.  
5.2. Go to Extensions → Installer.  
5.3. Locate the plugin and click the **Install** button.  
5.4. Switch to configuration mode.  
5.5. Edit the module:
 * Fill in the required fields in the form
 * Click **Save**

## 6. Plugin Uninstallation

### 6.1. Remove via Admin Panel

6.1.1. Log in to the OpenCart Admin Panel.  
6.1.2. Navigate to the payment modules section:   
 * Extensions (1) → Installer (2)
6.1.3. Locate the plugin and click **Uninstall** (3).  

<img src="/images/opencart-delete-1.png" width=60% height=60%>

### 6.2. Manually Delete Plugin Files  

Remove the following files manually:  

* `admin/controller/payment/payneteasy_form.php`
* `admin/language/english/payment/payneteasy_form.php`
* `admin/model/payment/payneteasy_form.php`
* `admin/view/image/payment/payneteasy.png`
* `admin/view/template/payment/payneteasy_form.php`
* `catalog/controller/payment/payneteasy_form.php`
* `catalog/language/english/payment/payneteasy_form.php`
* `catalog/model/payment/payneteasy_form.php`
* `catalog/view/theme/default/template/payment/payneteasy_form_checkout.php`
* `catalog/view/theme/default/template/payment/payneteasy_form_error.php`
* `vendor/composer/`
* `vendor/payneteasy/`
* `vendor/autoload.php`

## 7. User Registration
  
7.1. Click the **My Account** button at the top of the main page. In the dropdown menu, select **Register** to open the User Registration page.  

<img src="/images/opencart-register-1.png" width=60% height=60%>

7.2. On the **Register Account** page, fill in all required personal information, set your password, agree to the _Privacy Policy_, and click the **Continue** button.  

<img src="/images/opencart-register-2.png" width=60% height=60%> <img src="/images/opencart-register-3.png" width=60% height=60%>

## 8. Payment Flow

8.1. Navigate to the desired product category (e.g., Phones & PDAs). Select a product and click the **Add to Cart** button.  

<img src="/images/opencart-1-upd.png" width=60% height=60%> <img src="/images/opencart-2.png" width=60% height=60%> <img src="/images/opencart-3.png" width=60% height=60%>
  
8.2. After adding the item to the cart, a success message will appear. Click the **Shopping Cart** button and choose:   
   * **View Cart** to review the cart contents
   * **Checkout** to proceed directly to the checkout

<img src="/images/opencart-4.png" width=60% height=60%> <img src="/images/opencart-5.png" width=60% height=60%> <img src="/images/opencart-6.png" width=60% height=60%>
  
8.3. On the _Checkout_ page:  
 * Fill in the required _Shipping Address_.
 * Choose a _Shipping Method_ and a _Payment Method_.
 * In the Payment Method Options pop-up, select the _PaynetEasy payment method_.
 * Click **Place Order** to continue.

<img src="/images/opencart-7.png" width=60% height=60%> <img src="/images/opencart-9.png" width=60% height=60%>
<img src="/images/opencart-8-2.png" width=60% height=60%> <img src="/images/opencart-10.png" width=60% height=60%>
<img src="/images/opencart-11.png" width=60% height=60%>

8.4. On the _PaynetEasy Payment Form_, enter your credit card details. Click **Process Payment** and wait for the transaction to complete.  

<img src="/images/opencart-12.png" width=60% height=60%>
<img src="/images/opencart-13.png" width=60% height=60%>
<img src="/images/opencart-14.png" width=60% height=60%>
