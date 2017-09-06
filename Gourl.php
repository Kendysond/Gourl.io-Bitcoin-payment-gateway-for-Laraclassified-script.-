<?php


    
namespace App\Plugins\gourl;

use App\Models\Post;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use App\Helpers\Payment;
use App\Models\Package;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
require app_path('Plugins/gourl/cryptobox.class.php');
use Cryptobox;

class Gourl extends Payment
{
    /**
     * Send Payment
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public static function sendPayment(Request $request, Post $post)
    {
       
        // Set URLs
        parent::$uri['previousUrl'] = str_replace(['#entryToken', '#entryId'], [$post->tmp_token, $post->id], parent::$uri['previousUrl']);
        parent::$uri['nextUrl'] = str_replace(['#entryToken', '#entryId', '#title'], [$post->tmp_token, $post->id, slugify($post->title)], parent::$uri['nextUrl']);
        parent::$uri['paymentCancelUrl'] = str_replace(['#entryToken', '#entryId'], [$post->tmp_token, $post->id], parent::$uri['paymentCancelUrl']);
        parent::$uri['paymentReturnUrl'] = str_replace(['#entryToken', '#entryId'], [$post->tmp_token, $post->id], parent::$uri['paymentReturnUrl']);
        
        // Get Pack infos
        $package = Package::find($request->input('package'));
        
        // Don't make a payment if 'price' = 0 or null
        if (empty($package) || $package->price <= 0) {
            return redirect(parent::$uri['previousUrl'] . '?error=package')->withInput();
        }
    
        // API Parameters
        $providerParams = [
            'cancelUrl'   => parent::$uri['paymentCancelUrl'],
            'returnUrl'   => parent::$uri['paymentReturnUrl'],
            'name'        => $package->name,
            'description' => $package->name,
            'amount'      => (!is_float($package->price)) ? floatval($package->price) : $package->price,
            'currency'    => $package->currency_code,
        ];
        // dd($request);
        // Local Parameters
        $localParams = [
            'payment_method' => $request->get('payment_method'),
            'post_id'        => $post->id,
            'package_id'     => $package->id,
        ];
        $localParams = array_merge($localParams, $providerParams);
       // Try to make the Payment
        

        // try {
            $message    = "";
            $path       = url('');
            $page_url   = "//".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."#gourlcryptolang"; // Current page url
             // selected_coin
            
            
            /**** CONFIGURATION VARIABLES ****/ 
            $amount = (float)$providerParams['amount'];
            if ($providerParams['currency'] == 'USD') {
               $amount = $providerParams['amount'];
            }else{
                return parent::paymentFailureActions($post, 'Invalid Currency');
            }
            $userID         = $post->user_id.'-'.$post->email;
            $userFormat     = "COOKIE";      // save userID in cookies (or you can use IPADDRESS, SESSION)
            $orderID        = $post->id.'-'.$post->user_id.'-'.time();   // invoice number 22
            $amountUSD      = $amount;             // invoice amount - 2.21 USD
            $period         = "NOEXPIRY";    // one time payment, not expiry
            $def_language   = "en";          // default Payment Box Language
            $public_key     = "-public key-"; // from gourl.io
            $private_key    = "- secret key-";// from gourl.io
            
    
    // Goto  https://gourl.io/info/memberarea/My_Account.html
    // You need to create record for each your coin and get private/public keys
    // Place Public/Private keys for all your available coins from $available_payments
    
  
    /********************************/


    // Re-test - that all keys for $available_payments added in $all_keys
    if (!in_array($def_payment, $available_payments)) $available_payments[] = $def_payment;  
    foreach($available_payments as $v)
    {
        if (!isset($all_keys[$v]["public_key"]) || !isset($all_keys[$v]["private_key"])) die("Please add your public/private keys for '$v' in \$all_keys variable");
        elseif (!strpos($all_keys[$v]["public_key"], "PUB"))  die("Invalid public key for '$v' in \$all_keys variable");
        elseif (!strpos($all_keys[$v]["private_key"], "PRV")) die("Invalid private key for '$v' in \$all_keys variable");
        elseif (strpos(CRYPTOBOX_PRIVATE_KEYS, $all_keys[$v]["private_key"]) === false) die("Please add your private key for '$v' in variable \$cryptobox_private_keys, file cryptobox.config.php.");
    }
    
    
    // Current selected coin by user
    $coinName = cryptobox_selcoin($available_payments, $def_payment);
    
    
    // Current Coin public/private keys
           /** PAYMENT BOX **/
            $options = array(
                    "public_key"  => $public_key,        // your public key from gourl.io
                    "private_key" => $private_key,       // your private key from gourl.io
                    "webdev_key"  => "",                 // optional, gourl affiliate key
                    "orderID"     => $orderID,           // order id or product name
                    "userID"      => $userID,            // unique identifier for every user
                    "userFormat"  => $userFormat,        // save userID in COOKIE, IPADDRESS or SESSION
                    "amount"      => '',                  // product price in coins OR in USD below
                    "amountUSD"   => $amountUSD,         // we use product price in USD
                    "period"      => $period,            // payment valid period
                    "language"    => $def_language       // text on EN - english, FR - french, etc
            );
            // Please read description of options here - https://gourl.io/api-php.html#options  

            
            // Initialise Payment Class
            $box = new Cryptobox ($options);

            // coin name
            $coinName = $box->coin_name(); 

    
            // Payment Received
            if ($box->is_paid()) 
            { 
                $text = "User will see this message during ".$period." period after payment has been made!"; // Example
                
                $text .= "<br>".$box->amount_paid()." ".$box->coin_label()."  received<br>";
               

            }  
            // Payment Not Received
            else 
            {
                $text = "The payment has not been made yet";
            }
    
                // Notification when user click on button 'Refresh'
                if (isset($_POST["cryptobox_refresh_"]))
                {
                    $message = "<div class='gourl_msg'>";
                    if (!$box->is_paid()) $message .= '<div style="margin:50px" class="well"><i class="fa fa-info-circle fa-3x fa-pull-left fa-border" aria-hidden="true"></i> '.str_replace(array("%coinName%", "%coinNames%", "%coinLabel%"), array($box->coin_name(), ($box->coin_label()=='DASH'?$box->coin_name():$box->coin_name().'s'), $box->coin_label()), json_decode(CRYPTOBOX_LOCALISATION, true)[CRYPTOBOX_LANGUAGE]["msg_not_received"])."</div>";
                    elseif (!$box->is_processed())
                    {
                        // User will see this message one time after payment has been made
                        $message .= '<div style="margin:70px" class="alert alert-success" role="alert"> '.str_replace(array("%coinName%", "%coinLabel%", "%amountPaid%"), array($box->coin_name(), $box->coin_label(), $box->amount_paid()), json_decode(CRYPTOBOX_LOCALISATION, true)[CRYPTOBOX_LANGUAGE][($box->cryptobox_type()=="paymentbox"?"msg_received":"msg_received2")])."</div>";
                        $box->set_status_processed();
                    }
                    $message .="</div>";
                }

                $endpoint = $box->cryptobox_json_url();

                $localParams['endpoint'] = $endpoint;
                Session::put('params', $localParams);
                Session::save();
                // $endpoint = 'http://localhost/gourl/test.php';
              
            ?>
            <!DOCTYPE html>
                <html lang="en">

                <head>

                      
                    <title><?php echo $coinName; ?> Pay with Coins</title>
                    <meta charset="utf-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                        
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" crossorigin="anonymous"></script>
                     <script src="<?php echo  url('images/gourl/cryptobox.min.js'); ?>" type='text/javascript'></script>
                   
                    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" crossorigin="anonymous">
                    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" crossorigin="anonymous">
                    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous">
                    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" crossorigin="anonymous"></script>
                    <style>.tooltip-inner { max-width: 350px; } </style>
                </head>

                <body>

                <!-- JQuery Payment Box Script, see https://github.com/cryptoapi/Payment-Gateway/blob/master/cryptobox.js#L14 -->

                <script> cryptobox_custom('<?php echo $endpoint; ?>', <?php echo intval($box->is_paid()); ?>, '<?php echo $path; ?>', 'gourl_', '<?php echo $providerParams['returnUrl']; ?>','<?php echo csrf_token(); ?>'); </script>
                <div id="gourlcryptocoins" align="center" style="margin: 80px 0 0 0">
                <div class="container theme-showcase" role="main">


                    <div class="gourl_loader">
                    
                        <div class="container text-center gourl_loader_button">
                            <a style="margin:80px 20px 40px 20px" href="<?php echo $page_url; ?>" class="btn btn-default btn-lg"><i class='fa fa-spinner fa-spin'></i> &#160; <?php echo $box->coin_name() ?> Box Loading ...</a>
                        </div>
                        
                        <div style="margin:70px;display:none" class="panel panel-danger gourl_cryptobox_error">
                        
                            <div class="panel-heading">
                                <h3 class="panel-title">Error !</h3>
                            </div>
                            
                            <div class="panel-body">
                                <div class="gourl_error_message"></div>
                            </div>
                            
                        </div>
                        
                    </div>


                    <div class="gourl_cryptobox_top" style="display:none">  
                    
                    
                        <?php echo $message; ?>
                        
                        
                            
                        <div class="row">
                        
                           
                            <div class="col-xs-6 col-md-3">
                                <div class="dropdown" style='margin-bottom:20px'>
                                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">Language<?php  echo " &#160; <span class='small'>" . json_decode(CRYPTOBOX_LOCALISATION, true)[CRYPTOBOX_LANGUAGE]["name"] . "</span>"; ?>
                                <span class="caret"></span></button>
                                <?php  echo display_language_box("en", "gourlcryptolang", false); ?>
                                </div>
                            </div>
                             
                            
                            <div class="col-xs-6 col-md-3 gourl_boxlogo_paid" style="display:none">
                                <div class='text-right'><img class='gourl_boxlogo' alt='logo' src='#'></div>
                                <br>
                            </div>
                            
                            <div class="col-xs-6 col-md-9 gourl_boxlogo_unpaid"  style="display:none">
                                <div class='text-right'><img class='gourl_boxlogo' alt='logo' src='#'></div>
                                <br>
                            </div>
                        
                        </div>
                     
                    </div>        
                    


                    
                    <div class="gourl_cryptobox_unpaid" style="display:none">        
                            
                        <div class="row">
                          
                            <div class="col-md-4">
                                <div class="panel panel-info">
                                
                                    <div class="panel-heading">
                                        <h3 class="panel-title">1. <span class="gourl_texts_instruction"></span></h3>
                                    </div>
                                    
                                    <div class="panel-body">
                                        <div>
                                            <ol>
                                                <li data-site="circle.com" data-url="https://www.circle.com/" class="gourl_texts_intro1b"></li>
                                                <li class="gourl_texts_intro2"></li>
                                                <li><b class="gourl_texts_intro3"></b></li>
                                            </ol>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                    
                            
                            <div class="col-md-4">
                                <div class="panel panel-primary">
                            
                                    <div class="panel-heading">
                                        <h3 class="panel-title gourl_addr_title">2. <span class="gourl_texts_coin_address"></span></h3>
                                    </div>
                                    
                                    <div class="panel-body">
                                        <div style="float:right; margin-bottom:10px">
                                            <a class='gourl_wallet_url' href='#'><img class='gourl_qrcode_image' alt='qrcode' data-size='100' src='#'></a>
                                        </div>
                                        <br>
                                        <div class="gourl_texts_send"></div>
                                        <br>
                                        <div><a class="gourl_addr gourl_wallet_url" href="#"></a> &#160; <a class="gourl_wallet_url gourl_wallet_open" href="#"><i class="fa fa-external-link" aria-hidden="true"></i></a></div>
                                    </div>
                                    
                                </div>
                            </div>
                    
                            
                            <div class="col-md-4">
                            
                                <div class="panel panel-warning">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">3. <span class="gourl_paymentcaptcha_amount"></span></h3>
                                    </div>
                                    <div class="panel-body">
                                        <span class="gourl_amount"></span> <span class="gourl_coinlabel"></span> + <a class="gourl_texts_fees gourl_fees_hint" href="#"></a>
                                        
                                    </div>
                                </div>
                                
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">4. <span class="gourl_paymentcaptcha_status"></span></h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="gourl_paymentcaptcha_statustext"></div>
                                    </div>
                                </div>
                                
                            </div>
                            
                        </div>
                    
                        <br>
                        
                        <!-- <form action="<?php echo $page_url; ?>" method="post"> -->
                            <input type="hidden" id="cryptobox_refresh_" name="cryptobox_refresh_" value="1">
                            <button style="margin:10px 20px" class="gourl_button_refresh btn btn-default btn-lg"></button>
                            <button style="margin:10px 20px" class="gourl_button_wait btn btn-info btn-lg" onclick="cryptobox_refresh('http://localhost/gourl/test.php')"></button>
                            <a href="<?php echo url()->current(); ?>" class=" btn btn-default btn-lg"><i class="fa fa-arrow-left" aria-hidden="true"></i> Go Back</a>

                        <!-- </form> -->
                        
                        <br><br><br>
                        
                        <div class="gourl_texts_btn_wait_hint"></div>
                    
                    </div>
                    
                    
                    
                    
                    
                    <div class="gourl_cryptobox_paid" style="display:none"> 
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-success">
                                
                                    <div class="panel-heading">
                                        <div style="float:right; margin-left:10px">  
                                            <span class="gourl_texts_total"></span>: <span class="gourl_amount"></span> <span class="gourl_coinlabel"></span>
                                        </div>
                                        <h3 class="panel-title gourl_paymentcaptcha_title">Result</h3>
                                    </div>
                                    
                                    <div class="panel-body text-center">
                                    
                                        <div style="float:left" class="gourl_paidimg">
                                            <br>
                                            <img style='border:0' src='https://coins.gourl.io/images/paid.png' alt='Successful'>
                                            <br><br>
                                        </div>
                                        
                                        <h3 style='color:#3caf00;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:22px;line-height:35px;font-weight:bold;' class="gourl_paymentcaptcha_successful">.</h3>
                                        
                                        <div class="gourl_paymentcaptcha_date"></div>
                                        
                                        <br>
                                        <a style="margin:10px 20px" href="#" class="gourl_button_details btn btn-info"></a>
                                        
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div> 
                  

                </body>
            </html>
            <?php
            // //Set other parameters as keys in the $postdata array
            
           
           
        // } catch (\Exception $e) {
            
             // return parent::paymentApiErrorActions($post, $e);
            
        // }
    }
    
    /**
     * @param $params
     * @param $post
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
     public static function paymentConfirmation($params, $post)
    {
        function validatePayment($postdata){
            // a. check if private key valid
            $valid_key = false;
            if (isset($postdata["private_key_hash"]) && strlen($postdata["private_key_hash"]) == 128 && preg_replace('/[^A-Za-z0-9]/', '', $postdata["private_key_hash"]) == $postdata["private_key_hash"])
            {
                $keyshash = array();
                $arr = explode("^", CRYPTOBOX_PRIVATE_KEYS);
                foreach ($arr as $v) $keyshash[] = strtolower(hash("sha512", $v));
                if (in_array(strtolower($postdata["private_key_hash"]), $keyshash)) $valid_key = true;
            }


            // b. alternative - ajax script send gourl.io json data
            if (!$valid_key && isset($postdata["json"]) && $postdata["json"] == "1")
            {
                $data_hash = $boxID = "";
                if (isset($postdata["data_hash"]) && strlen($postdata["data_hash"]) == 128 && preg_replace('/[^A-Za-z0-9]/', '', $postdata["data_hash"]) == $postdata["data_hash"]) { $data_hash = $postdata["data_hash"]; unset($postdata["data_hash"]); }
                if (isset($postdata["box"]) && is_numeric($postdata["box"]) && $postdata["box"] > 0) $boxID = intval($postdata["box"]);
                
                if ($data_hash && $boxID)
                {
                    $private_key = "";
                    $arr = explode("^", CRYPTOBOX_PRIVATE_KEYS);
                    foreach ($arr as $v) if (strpos($v, $boxID."AA") === 0) $private_key = $v;
                
                    if ($private_key)
                    {
                        $data_hash2 = strtolower(hash("sha512", $private_key.json_encode($postdata).$private_key));
                        if ($data_hash == $data_hash2) $valid_key = true;
                    }
                    unset($private_key);
                }
                $valid_key = true;
                
                if (!$valid_key){

                    $box_status = "Error! Invalid Json Data sha512 Hash!";
                    $result_status = 'error';
                return ['result' => $result_status,'message' => $box_status];

               
                }
                
            }


            // c.
            if ($postdata) foreach ($postdata as $k => $v) if (is_string($v)) $postdata[$k] = trim($v);



            // d.
            if (isset($postdata["plugin_ver"]) && !isset($postdata["status"]) && $valid_key)
            {
                 $box_status = "cryptoboxver_" . (CRYPTOBOX_WORDPRESS ? "wordpress_" . GOURL_VERSION : "php_" . CRYPTOBOX_VERSION);
                 $result_status = 'error';
                return ['result' => $result_status,'message' => $box_status];

            }

            // e.
            if (isset($postdata["status"]) && in_array($postdata["status"], array("payment_received", "payment_received_unrecognised")) &&
                    $postdata["box"] && is_numeric($postdata["box"]) && $postdata["box"] > 0 && $postdata["amount"] && is_numeric($postdata["amount"]) && $postdata["amount"] > 0 && $valid_key)
            {
                
                foreach ($postdata as $k => $v)
                {
                    if ($k == "datetime")                       $mask = '/[^0-9\ \-\:]/';
                    elseif (in_array($k, array("err", "date", "period")))       $mask = '/[^A-Za-z0-9\.\_\-\@\ ]/';
                    else                                $mask = '/[^A-Za-z0-9\.\_\-\@]/';
                    if ($v && preg_replace($mask, '', $v) != $v)    $postdata[$k] = "";
                }
                
                if (!$postdata["amountusd"] || !is_numeric($postdata["amountusd"]))   $postdata["amountusd"] = 0;
                if (!$postdata["confirmed"] || !is_numeric($postdata["confirmed"]))   $postdata["confirmed"] = 0;
                
                
                $dt         = gmdate('Y-m-d H:i:s');
                $obj        = run_sql("select paymentID, txConfirmed from crypto_payments where boxID = ".$postdata["box"]." && orderID = '".$postdata["order"]."' && userID = '".$postdata["user"]."' && txID = '".$postdata["tx"]."' && amount = ".$postdata["amount"]." && addr = '".$postdata["addr"]."' limit 1");
                
                
                $paymentID      = ($obj) ? $obj->paymentID : 0;
                $txConfirmed    = ($obj) ? $obj->txConfirmed : 0; 
                
                // Save new payment details in local database
                if (!$paymentID)
                {
                    $sql = "INSERT INTO crypto_payments (boxID, boxType, orderID, userID, countryID, coinLabel, amount, amountUSD, unrecognised, addr, txID, txDate, txConfirmed, txCheckDate, recordCreated)
                            VALUES (".$postdata["box"].", '".$postdata["boxtype"]."', '".$postdata["order"]."', '".$postdata["user"]."', '".$postdata["usercountry"]."', '".$postdata["coinlabel"]."', ".$postdata["amount"].", ".$postdata["amountusd"].", ".($postdata["status"]=="payment_received_unrecognised"?1:0).", '".$postdata["addr"]."', '".$postdata["tx"]."', '".$postdata["datetime"]."', ".$postdata["confirmed"].", '$dt', '$dt')";

                    $paymentID = run_sql($sql);
                    
                    $box_status = "cryptobox_newrecord";
                }
                // Update transaction status to confirmed
                elseif ($postdata["confirmed"] && !$txConfirmed)
                {
                    $sql = "UPDATE crypto_payments SET txConfirmed = 1, txCheckDate = '$dt' WHERE paymentID = $paymentID LIMIT 1";
                    run_sql($sql);
                    
                    $box_status = "cryptobox_updated";
                }
                else 
                {
                    $box_status = "cryptobox_nochanges";
                }
                
                
                /**
                 *  User-defined function for new payment - cryptobox_new_payment(...)
                 *  For example, send confirmation email, update database, update user membership, etc.
                 *  You need to modify file - cryptobox.newpayment.php
                 *  Read more - https://gourl.io/api-php.html#ipn
                     */

                if (in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated")) && function_exists('cryptobox_new_payment')) cryptobox_new_payment($paymentID, $postdata, $box_status);
                $result_status = 'success';
            }else{
                $box_status = "Only POST Data Allowed or Invalid Key";
                $result_status = 'error';
               
            }

            return ['result' => $result_status,'message' => $box_status];
        }
        $request = file_get_contents($params['endpoint']);
        parent::$uri['previousUrl'] = str_replace(['#entryToken', '#entryId'], [$post->tmp_token, $post->id], parent::$uri['previousUrl']);
        parent::$uri['nextUrl'] = str_replace(['#entryToken', '#entryId', '#title'], [$post->tmp_token, $post->id, slugify($post->title)], parent::$uri['nextUrl']);
        $result = NULL;
        // $request = file_get_contents('http://localhost/gourl/test.php');
        if ($request) {
           $result = json_decode($request, false);
          $postdata = json_decode($request, true);
          if ($result->status == 'payment_received') {
           $vresult =  validatePayment($postdata);
           if ($vresult['result'] == 'success') {
               $params['transaction_id'] = $result->tx;
                return parent::paymentConfirmationActions($params, $post);
           }else{
                return parent::paymentFailureActions($post, $vresult['message']);
           }
          }
        }else{
            return parent::paymentFailureActions($post, 'Unable to verify transaction');

        }
       
    }
    
    /**
     * @return bool
     */
    public static function installed()
    {
        $paymentMethod = PaymentMethod::active()->where('name', 'LIKE', 'gourl')->first();
        if (empty($paymentMethod)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @return bool
     */
    public static function install()
    {
        // Remove the plugin entry
        self::uninstall();
        
        // Plugin data
        $data = [
            'name'         => 'gourl',
            'display_name' => 'gourl',
            'description'  => 'Payment with Gourl(Bitcoin)',
            'has_ccbox'    => 0,
            'lft'          => 0,
            'rgt'          => 0,
            'depth'        => 1,
            'active'       => 1,
        ];
        // dd($data);
        
        try {
            // Create plugin data
            $paymentMethod = PaymentMethod::create($data);
            if (empty($paymentMethod)) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @return bool
     */
    public static function uninstall()
    {
        $deletedRows = PaymentMethod::where('name', 'LIKE', 'gourl')->delete();
        if ($deletedRows <= 0) {
            return false;
        }
        
        return true;
    }
}
