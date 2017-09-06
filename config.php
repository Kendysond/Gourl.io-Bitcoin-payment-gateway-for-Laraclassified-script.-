<?php
/**
 *  ARRAY OF ALL YOUR CRYPTOBOX PRIVATE KEYS
 *  Place values from your gourl.io signup page
 *  array("your_privatekey_for_box1", "your_privatekey_for_box2 (otional), etc...");
 */
 
 $cryptobox_private_keys = array('14676AAo79c3Speedcoin77SPDPRVVL0JG7w3jg0Tc5Pfi34U8','14552AAvGwBcBitcoin77BTCPRVmBYSkvtHksraLCBeN1DcHik');




 define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));
 unset($cryptobox_private_keys); 
 
return [

    'gourl' => [
        'mode'      => env('PAYSTACK_MODE', 'sandbox'),
        'tsk'  => env('PAYSTACK_TSK', ''),
        'tpk'  => env('PAYSTACK_TPK', ''),
        'lsk'  => env('PAYSTACK_TSK', ''),
        'lpk'  => env('PAYSTACK_TPK', ''),
    ],

];