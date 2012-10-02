AstinaPaymentBundle
===================

Datatrans Provider
------------------

Service configuration:

    <service id="astina_payment.provider" class="Astina\PaymentBundle\Provider\Datatrans\Provider">
        <argument>%astina_payment.datatrans.merchantid%</argument>
        <argument>%astina_payment.datatrans.serviceurl%</argument>
        <argument>%astina_payment.datatrans.authorizexmlurl%</argument>
        <argument>%astina_payment.datatrans.capturexmlurl%</argument>
        <argument>%astina_payment.datatrans.sign%</argument>
        <argument>%astina_payment.datatrans.sign%</argument>
        <argument type="service" id="logger" />
    </service>

Paypal Provider
------------------

The Paypal provider is using the NVP API. See the [documentation](https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_NVPAPIOverview) for details.

The following API methods are implemented:

- [SetExpressCheckout](https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout) in `createPaymentUrl()`
- [GetExpressCheckoutDetails](https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_GetExpressCheckoutDetails) in `createTransactionFromRequest()`
- [DoExpressCheckoutPayment](https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoExpressCheckoutPayment) in `captureTransaction()`

Service configuration:

    <service id="astina_payment.provider" class="Astina\PaymentBundle\Provider\Paypal\Provider">
        <argument>%astina_payment.paypal.api_username%</argument>
        <argument>%astina_payment.paypal.api_password%</argument>
        <argument>%astina_payment.paypal.api_signature%</argument>
        <argument>%astina_payment.paypal.api_endpoint%</argument>
        <argument>%astina_payment.paypal.paypal_url%</argument>
        <argument>%astina_payment.paypal.subject%</argument>
        <argument type="service" id="logger" />
        <argument>%astina_payment.paypal.version%</argument> <!-- optional, defaults to 53.0 -->
    </service>

Saferpay Provider
------------------

The Saferpay provider is using the HTTPS API (V4.1.6).

Documenation: https://astina.atlassian.net/wiki/download/attachments/3932162/Saferpay+Payment+Page+V4.1.6+EN.pdf

Service Configuration:

    <service id="astina_payment.provider" class="Astina\PaymentBundle\Provider\Saferpay\Provider">
        <argument>%astina_payment.saferpay.endpoint%</argument>
        <argument>%astina_payment.saferpay.accountId%</argument>
        <argument>%astina_payment.saferpay.vtconfig%</argument> <!-- optional -->
        <argument type="service" id="logger" />
    </service>

