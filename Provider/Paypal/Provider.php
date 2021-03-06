<?php

namespace Astina\Bundle\PaymentBundle\Provider\Paypal;

use Astina\Bundle\PaymentBundle\Provider\TransactionInterface;
use Astina\Bundle\PaymentBundle\Provider\ProviderInterface;
use Astina\Bundle\PaymentBundle\Provider\OrderInterface;
use Astina\Bundle\PaymentBundle\Provider\Paypal\Transaction;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class Provider implements ProviderInterface
{
    private $apiUsername;

    private $apiPassword;

    private $apiSignature;

    private $apiEndpoint;

    private $paypalUrl;

    private $subject;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    private $version;

    public function __construct($apiUsername, $apiPassword, $apiSignature, $apiEndpoint, $paypalUrl,
                                $subject, LoggerInterface $logger, $version = '63.0')
    {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->apiSignature = $apiSignature;
        $this->apiEndpoint = $apiEndpoint;
        $this->paypalUrl = $paypalUrl;
        $this->subject = $subject;
        $this->logger = $logger;
        $this->version = $version;
    }

    function createTransaction(OrderInterface $order = null)
    {
        $transaction = new Transaction();

        if ($order) {
        	$transaction->setAmount($order->getTotalPrice());
        	$transaction->setCurrency($order->getCurrency());	
        }

        return $transaction;
    }

    function authorizeTransaction(TransactionInterface $transaction)
    {
        // authorization is done in createPaymentUrl()
    }

    function captureTransaction(TransactionInterface $transaction)
    {
        $apiParams = array(
            'TOKEN' => $transaction->getTransactionToken(),
            'PAYERID' => $transaction->getPayerId(),
            // 'PAYMENTACTION' => $transaction->getRequestType() ?: 'Sale', // deprecated
            'PAYMENTREQUEST_0_PAYMENTACTION' => $transaction->getRequestType() ?: 'Sale',
            'PAYMENTREQUEST_0_AMT' => ($transaction->getAmount() / 100),
            'PAYMENTREQUEST_0_CURRENCYCODE' => $transaction->getCurrency(),
        );
        if ($ref = $transaction->getReference()) {
            $apiParams['PAYMENTREQUEST_0_DESC'] = $ref;
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $apiParams['IPADDRESS'] = $_SERVER['REMOTE_ADDR'];
        }

        $response = $this->apiCall('DoExpressCheckoutPayment', $apiParams);

        if (isset($response['PAYMENTINFO_0_TRANSACTIONID'])) {
            $transaction->setTransactionId($response['PAYMENTINFO_0_TRANSACTIONID']);
        }
    }

    function createPaymentUrl(TransactionInterface $transaction, $successUrl = null, $errorUrl = null, $cancelUrl = null, array $params = array())
    {
        $params = array_merge(array(
            'AMT' => ($transaction->getAmount() / 100),
            //'PAYMENTACTION' => $transaction->getRequestType(), // deprecated
            'PAYMENTREQUEST_0_PAYMENTACTION' => $transaction->getRequestType(),
            'RETURNURl' => $successUrl,
            'CANCELURL' => $cancelUrl,
            'CURRENCYCODE' => $transaction->getCurrency(),
            'NOSHIPPING' => '1',
            'ALLOWNOTE' => '0',
            'ADDROVERRIDE' => '0',
            'LOCALECODE' => \Locale::getDefault(),
            'SOLUTIONTYPE' => 'Sole', // no PayPal account required
            'LANDINGPAGE' => 'Billing', // Billing – Non-PayPal account, Login – PayPal account login
        ), $params);

        $response = $this->apiCall('SetExpressCheckout', $params);

        $token = $response['TOKEN'];

        $url = $this->paypalUrl . $token;

        return $url;
    }

    public function createTransactionFromRequest(Request $request)
    {
        $apiParams = array(
            'TOKEN' => $request->get('token'),
        );

        $response = $this->apiCall('GetExpressCheckoutDetails', $apiParams);

        $transaction = new Transaction();
        $transaction->setAmount($response['AMT'] * 100);
        $transaction->setCurrency($response['CURRENCYCODE']);
        $transaction->setTransactionToken($response['TOKEN']);
        $transaction->setPayerId($response['PAYERID']);
        $transaction->setStatus($response['ACK']);

        return $transaction;
    }

    private function apiCall($method, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);

        //NVPRequest for submitting to server

        $params['METHOD'] = $method;
        $params['VERSION'] = $this->version;
        $params['PWD'] = $this->apiPassword;
        $params['USER'] = $this->apiUsername;
        $params['SIGNATURE'] = $this->apiSignature;

        $data = array();
        foreach ($params as $name => &$value) {
            $data[] = sprintf('%s=%s', urlencode($name), urlencode($value));
        }

        $this->logger->debug('Sending Paypal API request', $data);

        $data = implode('&', $data);

        //setting the nvpreq as POST FIELD to curl
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //getting response from server
        $response = curl_exec($ch);

        if (!$response) {
            throw new ApiException('Paypal API unreachable');
        }

        //convrting NVPResponse to an Associative Array
        $nvpResArray = $this->deformatNVP($response);

        $this->logger->debug('Received Paypal API response', $nvpResArray);

        if (!array_key_exists('ACK', $nvpResArray) || strtolower($nvpResArray['ACK']) != 'success') {
            throw new ApiException('Paypal API call failed', $nvpResArray);
        }

        return $nvpResArray;
    }

    private function deformatNVP($nvpstr)
    {
        $intial=0;
        $nvpArray = array();

        while(strlen($nvpstr)){
            //postion of Key
            $keypos= strpos($nvpstr,'=');
            //position of value
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval=substr($nvpstr,$intial,$keypos);
            $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] =urldecode( $valval);
            $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
        }
        return $nvpArray;
    }
}
