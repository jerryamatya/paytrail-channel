<?php
/**
 * Created by PhpStorm.
 * User: jerryamatya
 * Date: 23/01/2021
 * Time: 9:29 PM
 */

namespace Jerry\Paytrail;


use Jerry\Paytrail\Contracts\PaytrailInterface;
use Jerry\Paytrail\Exceptions\ValidationException;

class PayTrailPayment implements PaytrailInterface
{
    public const DEFAULT_PAYMENT_URL = 'https://payment.paytrail.com/channel-payment';

    private const VERSION = 1;
    private const CURRENCY = 'EUR';
    private const CULTURE = 'fi_FI';
    private $NOTIFY_ADDRESS, $RETURN_ADDRESS, $CANCEL_ADDRESS ;


    //private $products = [];
    private $paymentData = [];

    private static $channelFields = [
        'CONTACT_EMAIL',
        'CONTACT_FIRSTNAME',
        'CONTACT_LASTNAME',
        'CONTACT_ADDR_STREET',
        'CONTACT_ADDR_ZIP',
        'CONTACT_ADDR_CITY',
        'CONTACT_ADDR_COUNTRY',
        'INCLUDE_VAT',
        'ITEMS',
    ];


    private static $productFields = [
        'ITEM_TITLE',
        'ITEM_NO',
        'ITEM_AMOUNT',
        'ITEM_PRICE',
        'ITEM_TAX',
        'ITEM_MERCHANT_ID',
        'ITEM_CP',
        'ITEM_DISCOUNT',
        'ITEM_TYPE'
    ];

    //https://resource.dev.mulas.fi/api/orderssuccess?ORDER_NUMBER=285&TIMESTAMP=1611771038&PAID=A2A6AF1E64&RETURN_AUTHCODE=A23556B4BC9E3491606856878EC56FE8

    private static $returnParameters = [
        'ORDER_NUMBER',
        'TIMESTAMP',
        'PAID',
        'RETURN_AUTHCODE'
    ];

    /**
     * PayTrailPayment constructor.
     * @param Merchant $merchant
     */
    public function __construct(Merchant $merchant)
    {
        $this->merchant = $merchant;


    }


    /**
     * @param $return_address
     * @param $cancel_address
     * @param $notify_address
     */
    public function setUrl($return_address, $cancel_address, $notify_address): PaytrailInterface
    {
        $this->NOTIFY_ADDRESS = $notify_address;
        $this->RETURN_ADDRESS = $return_address;
        $this->CANCEL_ADDRESS = $cancel_address;
        return $this;

    }

    /**
     * @param string $orderNumber
     * @param array $contactDetails
     * @return array
     */

    public function createPayment(string $orderNumber, array $channelFields): PaytrailInterface
    {
        $this->paymentData['CHANNEL_ID'] = $this->merchant->channelId;
        $this->paymentData['ORDER_NUMBER'] = $orderNumber;
        $this->paymentData['CURRENCY'] = self::CURRENCY;
        $this->paymentData['RETURN_ADDRESS'] = $this->RETURN_ADDRESS;
        $this->paymentData['CANCEL_ADDRESS'] = $this->CANCEL_ADDRESS;
        $this->paymentData['NOTIFY_ADDRESS'] = $this->NOTIFY_ADDRESS;
        $this->paymentData['VERSION'] = self::VERSION;
        $this->paymentData['CULTURE'] = self::CULTURE;
        $this->paymentData['PRESELECTED_METHOD'] = isset($channelFields['PRESELECTED_METHOD']) ? $channelFields['PRESELECTED_METHOD'] : '';
        $this->paymentData['CONTACT_TELNO'] = isset($channelFields['CONTACT_TELNO']) ? $channelFields['CONTACT_TELNO'] : '';
        $this->paymentData['CONTACT_CELLNO'] = isset($channelFields['CONTACT_CELLNO']) ? $channelFields['CONTACT_CELLNO'] : '';

        $this->validatePaymentData($channelFields);
        return $this;
    }

    public function addProducts(array $products): PaytrailInterface
    {
        $this->validateProduct($products);
        $this->paymentData['items'] = $products;
        $this->paymentData['AUTHCODE'] = $this->calculateAuthcode($this->paymentData);
        return $this;
    }

    public function getFromJson(): array
    {
        return $this->paymentData;
    }
    /**
     * Calculate payment authcode.
     *
     * @param array $paymentData
     * @param Merchant $merchant
     * @return string
     */
    private function calculateAuthcode(array $paymentData): string
    {
        $hashDataItems='';
        foreach ($paymentData['items'] as $item){
            $hashDataItems = $hashDataItems.implode('|', $item);
        }
        unset($paymentData['items']);

        $hashDataChannels = implode('|', $paymentData);
        $hashData = $this->merchant->channelSecret.'|'.$hashDataChannels.'|'.$hashDataItems;
        return strToUpper(md5($hashData));
    }

    /**
     * Calculate expected return authcode.
     *
     * @param array $returnParameters
     * @param  Merchant $merchant
     * @return string
     */
    private function calculateReturnAuthCode(array $returnParameters): string
    {

        return strtoUpper(md5($returnParameters['ORDER_NUMBER'].'|'.$returnParameters['TIMESTAMP'].'|'.$returnParameters['PAID'].'|'.$this->merchant->channelSecret));


    }

    public function returnAuthcodeIsValid(array $returnParameters): bool
    {
        $this->validateReturnAuthCode($returnParameters);

        return ($this->calculateReturnAuthCode($returnParameters) == $returnParameters['RETURN_AUTHCODE']) ? :false;
    }


    private function validatePaymentData($channelFields)
    {
        foreach (self::$channelFields as $field){
            if(!array_key_exists($field, $channelFields)){
                throw new ValidationException($field." filed is missing");
            }
            $this->paymentData['CONTACT_EMAIL'] = $channelFields['CONTACT_EMAIL'];
            $this->paymentData['CONTACT_FIRSTNAME'] = $channelFields['CONTACT_FIRSTNAME'];
            $this->paymentData['CONTACT_LASTNAME'] = $channelFields['CONTACT_LASTNAME'];
            $this->paymentData['CONTACT_COMPANY'] = isset($channelFields['CONTACT_COMPANY']) ? $channelFields['CONTACT_COMPANY']:'';
            $this->paymentData['CONTACT_ADDR_STREET'] = $channelFields['CONTACT_ADDR_STREET'];
            $this->paymentData['CONTACT_ADDR_ZIP'] = $channelFields['CONTACT_ADDR_ZIP'];
            $this->paymentData['CONTACT_ADDR_CITY'] = $channelFields['CONTACT_ADDR_CITY'];
            $this->paymentData['CONTACT_ADDR_COUNTRY'] = $channelFields['CONTACT_ADDR_COUNTRY'];
            $this->paymentData['INCLUDE_VAT'] = $channelFields['INCLUDE_VAT'];
            $this->paymentData['ITEMS'] = $channelFields['ITEMS'];
        }
    }


    private function validateProduct($productFields)
    {
        foreach (self::$productFields as$key=>$field){
            foreach ($productFields as $postField){
                if(is_array($postField) == false) {
                    throw new ValidationException('Products should be array.');
                }
                if(!array_key_exists($field, $postField)){
                    throw new ValidationException($field." filed is missing");
                }
            }
        }
    }
    private function validateReturnAuthCode($returnParameters)
    {
        foreach (self::$returnParameters as $field){
            if(!array_key_exists($field, $returnParameters)){
                throw new ValidationException($field." filed is missing");
            }
        }
    }
}