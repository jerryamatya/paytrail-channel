<?php

namespace Jerry\Paytrail;
/**
 * Created by PhpStorm.
 * User: jerryamatya
 * Date: 23/01/2021
 * Time: 8:31 PM
 */
class Merchant
{
    public $channelSecret;
    public $channelId;
    public static function create(string $channelId, string $channelSecret ): self
    {
        $merchant = new self();
        $merchant->channelSecret = $channelSecret;
        $merchant->channelId = $channelId;

        return $merchant;
    }

}