<?php

namespace App\Services\Payment\Razorpay;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Errors\Error;

use Exception;

class RazorPayHelper
{
    private $apiClient;
    private $razorPayConfig;

    public function __construct(){
        $this->razorPayConfig=config('razorpay');
        $this->apiClient=new Api($this->razorPayConfig['key_id'],$this->razorPayConfig['key_secret']);
    }

    /**
     * Create order function
     *
     * @param array  array of parameters to create order like amount,currency
     *
     * @return string  - newly created order id
     */

     public function createOrder($data)
    {
        try{
            $amount=$data['amount'];

            if(!isset($data['currency'])){
                $currency=$this->razorPayConfig['default_currency'];
            }
            else{
                $currency=$data['currency'];
            }

            $serviceCharge=$this->calculateServiceCharge($amount);

            $receiptId='order_receipt_'.$this->generateRandomId();
            $amount=$amount+$serviceCharge;
            $amount=$amount*100;

            $orderData=['receipt'=>$receiptId,'amount'=>$amount,'currency'=>$currency];

            $order=$this->apiClient->order->create($orderData);
            return $order['id'];

        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }

    }

    /**
     * Verify after payment payload
     *
     */

    public function verifySignature($razorpayPayload){
        try{
            $this->apiClient->Utility->verifyPaymentSignature($razorpayPayload);
            return true;
        }catch(SignatureVerificationError $e){
            throw new Exception('Verification failed');
        }
    }

    public function getPaymentStatus($data){
        try{
            $paymentId=$data['razorpay_payment_id'];
            $paymentDetails=$this->apiClient->payment->fetch($paymentId);

            $paymentStatus=$paymentDetails->status;
            $orderId=$paymentDetails

            return $paymentDetails;

        }
        catch(Exception $e){
            throw new Exception('Cant get payment details');
        }
    }

    public function generateRandomId(){
        return uniqid();
    }

    public function calculateServiceCharge($amount){
        $servicePercentage=$this->razorPayConfig['service_charge'];
        return $amount*$servicePercentage/100;
    }


}
