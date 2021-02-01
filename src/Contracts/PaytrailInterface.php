<?php
namespace Jerry\Paytrail\Contracts;

/**
 * Created by PhpStorm.
 * User: jerryamatya
 * Date: 23/01/2021
 * Time: 9:02 PM
 */

interface PaytrailInterface
{
    public function createPayment(string $orderNumber, array $channelFields): self ;

    public function addProducts(array $products): self ;

    public function returnAuthcodeIsValid(array $returnParameters): bool;

    public function setUrl($RETURN_ADDRESS, $CANCEL_ADDRESS, $NOTIFY_ADDRESS): self ;

    public function getFromJson(): array ;

}