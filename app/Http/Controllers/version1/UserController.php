<?php

namespace App\Http\Controllers\version1;


use DateTime;
use App\Models\version1\User;
use App\Http\Controllers\Controller;
use App\Mail\version1\GeneralMailToAdmin;
use App\Mail\version1\NewOrderMailToAdmin;
use Illuminate\Support\Facades\Mail;
use App\Mail\version1\RequestCollectionCallbackMailToAdmin;
use App\Models\version1\CollectionCallBack;
use App\Models\version1\CollectionCallBackRequest;
use App\Models\version1\Country;
use App\Models\version1\Discount;
use App\Models\version1\Message;
use App\Models\version1\Notification;
use App\Models\version1\Order;
use App\Models\version1\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


ini_set('memory_limit','1024M');
ini_set("upload_max_filesize","100M");
ini_set("max_execution_time",60000); //--- 10 minutes
ini_set("post_max_size","135M");
ini_set("file_uploads","On");

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION REGISTER EMAIL AND SENDS LOGIN CODE
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function enterApp(Request $request)
    {

        // MAKING SURE THE INPUT HAS THE EXPECTED VALUES
        $validatedData = $request->validate([
            "user_country" => "bail|required|max:100",
            "user_phone" => "bail|required|max:10",
            "user_first_name" => "bail|required|max:100",
            "user_last_name" => "bail|required|max:100",
            "invite_code" => "bail|max:15",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        // MAKING SURE VERSION CODE IS ALLOWED
        if(strtoupper($request->app_type) == "ANDROID" && 
        ($request->app_version_code < intval(config('app.androidminvc')) || $request->app_version_code > intval(config('app.androidmaxvc')))
        ){
            return response([
                "status" => "error", 
                "app_version_code" => $request->app_version_code , 
                "androidminvc" => config('app.androidminvc'), 
                "androidmaxvc" => config('app.androidmaxvc'), 
                "message" => "Please update your app from the Google Play Store."
            ]);
        }

        if(strtoupper($request->app_type) == "IOS" && 
        ($request->app_version_code < intval(config('app.iosminvc')) || $request->app_version_code > intval(config('app.iosmaxvc')))
        ){
            return response([
            "status" => "error", 
            "message" => "Please update your app from the Apple App Store."
            ]);
        }

        if(strtoupper($request->app_type) != "IOS" && strtoupper($request->app_type)  != "ANDROID"){
            return response([
                "status" => "error", 
                "message" => "Please update your app."
                ]);
        }

        $user_country = Country::where('country_real_name', '=', $request->user_country)->first();
        if($user_country === null){
            return response([
                "status" => "error", 
                "message" => "Country selection error."
                ]);
        }
        
        if($user_country->country_real_name == "GHANA"){
            //$numOld = "2712345678";
            //$phone = preg_replace('~^(?:0?1|601)~','+601', $phone);
            $user_phone_correct = preg_replace('/^(?:\+?0|0)?/','+233', $request->user_phone); // > $num = "+2712345678"
        } else {
            return response([
                "status" => "error", 
                "message" => "Service not available in your country."
            ]);
        }

        
        //CHECKING IF USER EXISTS
        $user1 = User::where('user_phone', '=', $user_phone_correct)->first();

        if($user1 === null){
            for ($i=5; $i < 10; $i++) { 
                $this_referral_code = substr(sha1(md5(time())), -$i);
                $user2 = User::where('user_referral_code', '=', $this_referral_code)->first();
                if($user2 === null){
                    break;
                }    
            }
    
            $userData["user_sys_id"] = date("Y-m-d-H-i-s") . UtilController::getRandomString(91);
            $userData["user_first_name"] = $request->user_first_name;
            $userData["user_last_name"] = $request->user_last_name;
            $userData["user_phone"] = $user_phone_correct;
            $userData["user_country_id"] = $user_country->country_id;
            $userData["user_referral_code"] = $this_referral_code;
            $userData["user_invitors_referral_code"] = $request->invite_code;
            $userData["user_notification_token_android"] = "";
            $userData["user_notification_token_web"] = "";
            $userData["user_notification_token_ios"] = "";
            $userData["user_flagged"] = false;
            $userData["user_flagged_reason"] = "";
            // SAVING APP TYPE VERSION CODE
            if(strtoupper($request->app_type) == "ANDROID"){
                $userData["user_android_app_version_code"] = $validatedData["app_version_code"];
            } else if(strtoupper($request->app_type) == "IOS"){
                $userData["user_ios_app_version_code"] = $validatedData["app_version_code"];
            } 
            $user1 = User::create($userData);

            if(!empty($request->invite_code)){
                //echo "1 here \n <br> \n ";
                $referrer_user = User::where('user_referral_code', '=', $request->invite_code)->first();
                if($referrer_user !== null){
                    //echo "2 here \n <br> \n ";
                    UtilController::addNotificationToUserQueue("Invite Code Used", "Someone just used your invite code. When they make their first order, you get a discount code.", $referrer_user->user_phone, 6011);
                    UtilController::sendNotificationToUser($referrer_user->user_notification_token_android,"normal","Invite Code Used - MeMaww", "Someone just used your invite code. When they make their first order, you get a discount code.");
                    UtilController::sendNotificationToUser($referrer_user->user_notification_token_ios,"normal","Invite Code Used - MeMaww", "Someone just used your invite code. When they make their first order, you get a discount code.");
                }
            }
        }

        $user1 = User::with('userCountry')->where("user_id", $user1->user_id)->latest()->first();

        $accessToken = $user1->createToken("authToken", ["use-mobile-apps-as-normal-user"])->accessToken;

        return response([
            "status" => "success", 
            "message" => "Sign-in successful",
            "user_phone_local" => $request->user_phone,
            "access_token" => $accessToken,
            "user" => $user1,
        ]);

    }

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION GETS THE PRICE AND RECORDS AN ORDER PENDING USER-CONFIRMATION
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    public function requestCollection(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }

        $validatedData = $request->validate([
            "collect_loc_raw" => "bail|max:100",
            "collect_loc_gps" => "bail|max:22",
            "collect_datetime" => "bail|max:5",
            "contact_person_phone" => "bail|max:10",
            "drop_loc_raw" => "bail|max:100",
            "drop_loc_gps" => "bail|max:22",
            "drop_datetime" => "bail|max:12",
            "smallitems_justwash_quantity" => "bail|integer|digits_between:-1,1000",
            "smallitems_washandiron_quantity" => "bail|integer|digits_between:-1,1000",
            "smallitems_justiron_quantity" => "bail|integer|digits_between:-1,1000",
            "mediumitems_justwash_quantity" => "bail|integer|digits_between:-1,1000",
            "mediumitems_washandiron_quantity" => "bail|integer|digits_between:-1,1000",
            "mediumitems_justiron_quantity" => "bail|integer|digits_between:-1,1000",
            "bigitems_justwash_quantity" => "bail|integer|digits_between:-1,1000",
            "bigitems_washandiron_quantity" => "bail|integer|digits_between:-1,1000",
            "special_instructions" => "bail|max:2000",
            "discount_code" => "bail|max:12",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
        

        if (empty($request->collect_datetime) || DateTime::createFromFormat('Y-m-d H:i:s', date("Y-m-d") . " " . $request->collect_datetime . ":00") === false) {
            return response(["status" => "error", "message" => "Fill in the pickup time"]);
        }

        if(empty($request->collect_loc_raw) && empty($request->collect_loc_gps)){
            return response(["status" => "error", "message" => "Fill in the pickup location"]);
        }

        if(empty($request->contact_person_phone)){
            return response(["status" => "error", "message" => "Fill in contact person's phone number"]);
        }

        if(($request->smallitems_justwash_quantity + $request->smallitems_washandiron_quantity + $request->smallitems_justiron_quantity + $request->bigitems_justwash_quantity + $request->mediumitems_justwash_quantity + $request->mediumitems_washandiron_quantity + $request->mediumitems_justiron_quantity + $request->bigitems_washandiron_quantity) <= 0)
        {
            return response(["status" => "error", "message" => "You have not set the number of items we are picking up."]);
        }
        
        $final_price = 0;
        $pay_online = "yes";
        $pay_on_pickup = "yes";
        $discount_percentage = 0;
        $discount_amount = 0;
        $discount_amount_usd = 0;
        $discount_id= null;
        $original_price = 0;


        $userCountry = Country::where("country_id", auth()->user()->user_country_id)->latest()->first();
        if(empty($userCountry->country_currency_symbol)){
            return response([
                "status" => "error", 
                "message" => "A currency error occurred."
            ]);
        }

        if(
            (($request->smallitems_justwash_quantity + $request->smallitems_washandiron_quantity + $request->smallitems_justiron_quantity) < 10)
            && (($request->mediumitems_justwash_quantity + $request->mediumitems_washandiron_quantity + $request->mediumitems_justiron_quantity) < 1)
            && (($request->bigitems_justwash_quantity + $request->bigitems_washandiron_quantity) < 1)
        ){
            return response([
                "status" => "error", 
                "message" => "Order for only lightweight items needs at least 10 items."
            ]);
        }

        if(
            (
            ($request->bigitems_washandiron_quantity * 30) 
            + ($request->bigitems_justwash_quantity * 20)
            + ($request->mediumitems_washandiron_quantity * 8) 
            + ($request->mediumitems_justiron_quantity * 5) 
            + ($request->mediumitems_justwash_quantity * 5)
            + ($request->smallitems_justwash_quantity * 1)
            + ($request->smallitems_washandiron_quantity * 1.5)
            + ($request->smallitems_justiron_quantity * 1)
            ) < 70
        ){
            $final_price_less_than_minimum = true;
        } else {
            $final_price_less_than_minimum = false;
        }

        if(auth()->user()->user_country_id == 81) { // GHANA
            

            // LIGHT WEIGHT ITEMS --- WASH AND FOLD
            if($request->smallitems_justwash_quantity > 0){
                $final_price = $final_price + ($request->smallitems_justwash_quantity * 1);
            }

            // LIGHT WEIGHT ITEMS --- WASH AND IRON
            if($request->smallitems_washandiron_quantity > 0){
                $final_price = $final_price + ($request->smallitems_washandiron_quantity * 1.5);
            }


            // LIGHT WEIGHT ITEMS --- JUST IRON
            if($request->smallitems_justiron_quantity > 0){
                $final_price = $final_price + ($request->smallitems_justiron_quantity * 1);
            }

            // MEDIUM WEIGHT ITEMS --- WASH AND FOLD
            if($request->mediumitems_justwash_quantity == 1 && $final_price_less_than_minimum){
                $final_price = $final_price + 25;
            } else if($request->mediumitems_justwash_quantity == 2 && $final_price_less_than_minimum){
                $final_price = $final_price + 30;
            } else if($request->mediumitems_justwash_quantity == 3 && $final_price_less_than_minimum){
                $final_price = $final_price + 35;
            } else if($request->mediumitems_justwash_quantity == 4 && $final_price_less_than_minimum){
                $final_price = $final_price + 40;
            } else if($request->mediumitems_justwash_quantity == 5 && $final_price_less_than_minimum){
                $final_price = $final_price + 45;
            } else if($request->mediumitems_justwash_quantity == 6 && $final_price_less_than_minimum){
                $final_price = $final_price + 50;
            } else if($request->mediumitems_justwash_quantity == 7 && $final_price_less_than_minimum){
                $final_price = $final_price + 55;
            }  else if($request->mediumitems_justwash_quantity == 8 && $final_price_less_than_minimum){
                $final_price = $final_price + 60;
            }  else if($request->mediumitems_justwash_quantity == 9 && $final_price_less_than_minimum){
                $final_price = $final_price + 65;
            }  else if($request->mediumitems_justwash_quantity == 10 && $final_price_less_than_minimum){
                $final_price = $final_price + 70;
            } else {
                $final_price = $final_price + ($request->mediumitems_justwash_quantity * 5);
            }

            // MEDIUM WEIGHT ITEMS --- WASH AND IRON
            if($request->mediumitems_washandiron_quantity == 1 && $final_price_less_than_minimum){
                $final_price = $final_price + 28;
            } else if($request->mediumitems_washandiron_quantity == 2 && $final_price_less_than_minimum){
                $final_price = $final_price + 36;
            } else if($request->mediumitems_washandiron_quantity == 3 && $final_price_less_than_minimum){
                $final_price = $final_price + 44;
            } else if($request->mediumitems_washandiron_quantity == 4 && $final_price_less_than_minimum){
                $final_price = $final_price + 52;
            } else if($request->mediumitems_washandiron_quantity == 5 && $final_price_less_than_minimum){
                $final_price = $final_price + 60;
            } else if($request->mediumitems_washandiron_quantity == 6 && $final_price_less_than_minimum){
                $final_price = $final_price + 68;
            } else if($request->mediumitems_washandiron_quantity == 7 && $final_price_less_than_minimum){
                $final_price = $final_price + 76;
            }  else if($request->mediumitems_washandiron_quantity == 8 && $final_price_less_than_minimum){
                $final_price = $final_price + 84;
            }  else if($request->mediumitems_washandiron_quantity == 9 && $final_price_less_than_minimum){
                $final_price = $final_price + 92;
            }  else if($request->mediumitems_washandiron_quantity == 10 && $final_price_less_than_minimum){
                $final_price = $final_price + 100;
            } else {
                $final_price = $final_price + ($request->mediumitems_washandiron_quantity * 8);
            }


            // MEDIUM WEIGHT ITEMS --- JUST IRON
            if($request->mediumitems_justiron_quantity == 1 && $final_price_less_than_minimum){
                $final_price = $final_price + 25;
            } else if($request->mediumitems_justiron_quantity == 2 && $final_price_less_than_minimum){
                $final_price = $final_price + 30;
            } else if($request->mediumitems_justiron_quantity == 3 && $final_price_less_than_minimum){
                $final_price = $final_price + 35;
            } else if($request->mediumitems_justiron_quantity == 4 && $final_price_less_than_minimum){
                $final_price = $final_price + 40;
            } else if($request->mediumitems_justiron_quantity == 5 && $final_price_less_than_minimum){
                $final_price = $final_price + 45;
            } else if($request->mediumitems_justiron_quantity == 6 && $final_price_less_than_minimum){
                $final_price = $final_price + 50;
            } else if($request->mediumitems_justiron_quantity == 7 && $final_price_less_than_minimum){
                $final_price = $final_price + 55;
            }  else if($request->mediumitems_justiron_quantity == 8 && $final_price_less_than_minimum){
                $final_price = $final_price + 60;
            }  else if($request->mediumitems_justiron_quantity == 9 && $final_price_less_than_minimum){
                $final_price = $final_price + 65;
            }  else if($request->mediumitems_justiron_quantity == 10 && $final_price_less_than_minimum){
                $final_price = $final_price + 70;
            } else {
                $final_price = $final_price + ($request->mediumitems_justiron_quantity * 5);
            }


            // BULKY ITEMS --- WASH AND FOLD
            if($request->bigitems_justwash_quantity == 1 && $final_price_less_than_minimum){
                $final_price = $final_price + 40;
            } else if($request->bigitems_justwash_quantity == 2 && $final_price_less_than_minimum){
                $final_price = $final_price + 50;
            } else if($request->bigitems_justwash_quantity == 3 && $final_price_less_than_minimum){
                $final_price = $final_price + 65;
            } else {
                $final_price = $final_price + ($request->bigitems_justwash_quantity * 20);
            }

            // BULKY ITEMS --- WASH AND IRON
            if($request->bigitems_washandiron_quantity == 1 && $final_price_less_than_minimum){
                $final_price = $final_price + 50;
            } else if($request->bigitems_washandiron_quantity == 2 && $final_price_less_than_minimum){
                $final_price = $final_price + 70;
            } else if($request->bigitems_washandiron_quantity == 3 && $final_price_less_than_minimum){
                $final_price = $final_price + 95;
            } else {
                $final_price = $final_price + ($request->bigitems_washandiron_quantity * 30);
            }

            $query1 = "SELECT * FROM discounts WHERE discount_restricted_to_user_id = ? AND discount_can_be_used = ? ORDER BY discount_id ASC LIMIT 1";
            $values1 = [auth()->user()->user_id, true];
            $discount1 = DB::select($query1, $values1);

            $original_price = $final_price;
            if(!empty($request->discount_code) && $final_price > 0){
                $query2 = "SELECT * FROM discounts WHERE discount_code = ? AND discount_can_be_used = ? ORDER BY discount_id ASC LIMIT 1";
                $values2 = [$request->discount_code, true];
                $discount2 = DB::select($query2, $values2);
    
                //$discount = Discount::where('discount_code', '=', $request->discount_code)->where('discount_restricted_to_user_id ','=', NULL)->where('message_receiver_id', auth()->user()->user_id)->first();
                if(!empty($discount2[0]->discount_percentage) && $discount2[0]->discount_percentage > 0 && (empty($discount2[0]->discount_restricted_to_user_id) || ($discount2[0]->discount_restricted_to_user_id == auth()->user()->user_id))){
                    $discount_id = $discount2[0]->discount_id;
                    $discount_percentage =  $discount2[0]->discount_percentage;
                    $discount_amount = $final_price * (($discount2[0]->discount_percentage)/100);
                    $discount_amount_usd = $discount_amount/config('app.one_dollar_to_one_ghana_cedi');
                    $final_price =  $final_price * ((100-$discount2[0]->discount_percentage)/100);
                    //var_dump($discount2); exit;
                }
            }
            
            if(empty($discount_id) && !empty($discount1[0]->discount_percentage) && $discount1[0]->discount_percentage > 0){
                $discount_id = $discount1[0]->discount_id;
                $discount_percentage =  $discount1[0]->discount_percentage;
                $discount_amount = $final_price * (($discount1[0]->discount_percentage)/100);
                $discount_amount_usd = $discount_amount/config('app.one_dollar_to_one_ghana_cedi');
                $final_price =  $final_price * ((100-$discount1[0]->discount_percentage)/100);
        }
            
            $final_price = strval($final_price);
            
        } else {
            return response([
                "status" => "error", 
                "message" => "Service not available in your country."
            ]);
        }
        
        $orderData["order_sys_id"] = auth()->user()->user_id . "_" .date("YmdHis") . UtilController::getRandomString(4);
        $orderData["order_user_id"] = auth()->user()->user_id;
        $orderData["order_laundrysp_id"] = 1; // MeMaww Ghana
        //$orderData["order_collection_biker_name"] = "";
        $orderData["order_collection_location_raw"] = $validatedData["collect_loc_raw"];
        $orderData["order_collection_location_gps"] = $validatedData["collect_loc_gps"];
        $orderData["order_collection_date"] = date("Y-m-d") . " " . $validatedData["collect_datetime"];
        $orderData["order_collection_contact_person_phone"] = $validatedData["contact_person_phone"];
        $orderData["order_dropoff_location_raw"] = $validatedData["collect_loc_raw"];
        $orderData["order_dropoff_location_gps"] = $validatedData["collect_loc_gps"];
        $orderData["order_dropoff_date"] = $validatedData["drop_datetime"];
        $orderData["order_dropoff_contact_person_phone"] = $validatedData["contact_person_phone"];
        $orderData["special_instructions"] = empty($validatedData["special_instructions"]) ? "" : $validatedData["special_instructions"];

        $orderData["order_country_id"] = auth()->user()->user_country_id;
        $orderData["order_user_countrys_currency"] = $userCountry->country_currency_symbol;
        $orderData["order_discount_id"] = $discount_id;
        $orderData["order_discount_amount_in_user_countrys_currency"] = $discount_amount;
        $orderData["order_discount_amount_in_dollars_at_the_time"] = $discount_amount_usd;
        $orderData["order_final_price_in_user_countrys_currency"] = $final_price;
        $orderData["order_final_price_in_dollars_at_the_time"] = $final_price/config('app.one_dollar_to_one_ghana_cedi');

        //$orderData["order_dropoff_biker_name"] = "";
        $orderData["order_lightweightitems_just_wash_quantity"] = $validatedData["smallitems_justwash_quantity"];
        $orderData["order_lightweightitems_wash_and_iron_quantity"] = $validatedData["smallitems_washandiron_quantity"];
        $orderData["order_lightweightitems_just_iron_quantity"] = $validatedData["smallitems_justiron_quantity"];


        $orderData["order_mediumitems_justwash_quantity"] = $validatedData["mediumitems_justwash_quantity"];
        $orderData["order_mediumitems_washandiron_quantity"] = $validatedData["mediumitems_washandiron_quantity"];
        $orderData["order_mediumitems_justiron_quantity"] = $validatedData["mediumitems_justiron_quantity"];

        $orderData["order_bulkyitems_just_wash_quantity"] = $validatedData["bigitems_justwash_quantity"];
        $orderData["order_bulkyitems_wash_and_iron_quantity"] = $validatedData["bigitems_washandiron_quantity"];
        $orderData["order_status"] = 0; //0=pending_user_confirmation, 1=pending_payment, 2-payment_made_pending_collector_assignment, 3-Collected, 4-Washing, 5-assigned_for_delivery, 6-completed
        $orderData["order_payment_status"] = 0; //0-pending, 1-paid-to-biker, 2-momo
        $orderData["order_payment_details"] = "";
        $orderData["order_flagged"] = false;
        $orderData["order_flagged_reason"] = "";
        $order = Order::create($orderData);
        
        return response([
            "status" => "success", 
            "pay_online" => $pay_online, 
            "pay_on_pickup" => $pay_on_pickup, 
            "original_price" => $userCountry->country_currency_symbol . strval($original_price), 
            "discount_percentage" => strval($discount_percentage) . "%", 
            "discount_amount" => $userCountry->country_currency_symbol . strval($discount_amount), 
            "price_final" => $userCountry->country_currency_symbol . strval($final_price), 
            "price_final_no_currency" => strval($final_price), 
            "price_final_no_currency_long" => sprintf("%012d", strval($final_price)), 
            "user_email" => auth()->user()->user_phone . "@memaww.com", 
            "txn_narration" => "Laundry pickup request by " . auth()->user()->user_last_name . " " . auth()->user()->user_first_name, 
            "txn_reference" => sprintf("%012d", $order->order_id), 
            "merchant_id" => config('app.payment_gateway_merchant_id'), 
            "merchant_api_user" => config('app.payment_gateway_merchant_api_user'), 
            "merchant_api_key" => config('app.payment_gateway_merchant_api_key'), 
            "merchant_test_api_key" => config('app.payment_gateway_merchant_test_api_key'), 
            "return_url" => config('app.url') . "/payment/" . $order->order_sys_id, 
            "message" => "Order created"
        ]);
    
    }
    
    public function updateOrderPaymentStatus(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    
        $validatedData = $request->validate([
            "order_id" => "bail|required|max:100",
            "order_payment_status" => "bail|required|max:100",
            "order_payment_details" => "bail|required|max:200",
            "order_payment_method" => "bail|required|max:200",
            "purge" => "bail|required|integer",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        
        //echo "ID: " . strval(intval($request->order_id));exit;
        $the_order = Order::where('order_id', '=', strval(intval($request->order_id)))->first();
        if($the_order === null){
            return response([
                "status" => "error", 
                "message" => "Order not found"
            ]);
        }

        if($the_order->order_user_id != auth()->user()->user_id){
            return response([
                "status" => "error", 
                "message" => "How did that happen???"
            ]);
        }

        if($the_order->order_status != 0){
            return response([
                "status" => "error", 
                "message" => "Order in advanced state"
            ]);
        }

        $payment_verify = UtilController::verifyPayStackTransaction($request->order_id);
        if($payment_verify->status != "approved" && $request->order_payment_status != "pay_on_pickup") {
            return response([
                "status" => "error", 
                "message" => "Payment verification failed"
            ]);
        }

        $first_order = Order::where('order_user_id', '=', auth()->user()->user_id)->get()->count();
        if($first_order == 1 && ($payment_verify->status == "approved" || $request->order_payment_status == "pay_on_pickup")){
            $invitors_user_id = User::where('user_referral_code', '=', auth()->user()->user_invitors_referral_code)->first();
            if($invitors_user_id != null){
                UtilController::giveDiscount(config('app.referral_discount_percentage'), $invitors_user_id->user_id, "MeMaww Auto", false, true, UtilController::getDatePlusOrMinusDays(new DateTime(), "+3 days", "Y-m-d"));
                UtilController::addNotificationToUserQueue("You have a discount", "Someone you referred placed an order so we gave you a discount.", $invitors_user_id->user_phone, 6011);
                UtilController::sendNotificationToUser($invitors_user_id->user_notification_token_android,"normal","Discount Received - MeMaww", "Someone you referred placed an order so we gave you a discount.");
                UtilController::sendNotificationToUser($invitors_user_id->user_notification_token_ios,"normal","Discount Received - MeMaww", "Someone you referred placed an order so we gave you a discount.");    
            }
        }

        

        if($payment_verify->status == "approved" || $request->order_payment_status == "pay_on_pickup"){
            $the_order->order_status = 1;
            $the_order->order_payment_method = $request->order_payment_method;
            $the_order->order_payment_status = $payment_verify->status == "approved" ? 1 : 0;
            $the_order->order_payment_details = $payment_verify->status == "approved" ? $payment_verify->reason : "pay_on_pickup";
            $the_order->save();

            //UPDATING DISCOUNT CODE USED
            if(!empty($the_order->order_discount_id)){
                $discount_used = Discount::where('discount_id', '=', $the_order->order_discount_id)->first();
                $discount_used->discount_can_be_used = false;
                $discount_used->save();
            }

            UtilController::addNotificationToUserQueue("Order Received", "Your order has been received. Expect a biker or call soon.", auth()->user()->user_phone, 6011);
            UtilController::sendNotificationToUser(auth()->user()->user_notification_token_android,"normal","Order Received - MeMaww", "Your order has been received. Expect a biker or call soon.");
            UtilController::sendNotificationToUser(auth()->user()->user_notification_token_ios,"normal","Order Received - MeMaww", "Your order has been received. Expect a biker or call soon.");
        
            $user_admin = User::where('user_id', '=', 1)->first();
            if($user_admin != null){
                UtilController::sendNotificationToUser($user_admin->user_notification_token_android, "normal","New Order From Client - MeMaww", "A Client of MeMaww has placed an order");
                UtilController::sendNotificationToUser($user_admin->user_notification_token_ios,"normal","New Order From Client - MeMaww", "A Client of MeMaww has placed an order");
            }
    

            $email_data = array(
                'pickup_time' => "Time: " . $the_order->order_collection_date,
                'pickup_location_raw' => $the_order->order_collection_location_raw,
                'pickup_location_gps' => $the_order->order_collection_location_gps,
                'user_name' => auth()->user()->user_first_name . " " . auth()->user()->user_last_name,
                'user_phone' => auth()->user()->user_phone,
                'order_status' => $the_order->getOrderStatusMessageAttribute(),
                'order_id' => $the_order->order_id,
                'order_time' => $the_order->created_at,
                'order_payment_amt' => $the_order->order_user_countrys_currency . $the_order->order_final_price_in_user_countrys_currency,
                'order_payment_status' => $payment_verify->status == "approved" ? "Paid" : "Pay On Pickup",
                'order_lightweightitems_just_wash_quantity' => $the_order->order_lightweightitems_just_wash_quantity,
                'order_lightweightitems_wash_and_iron_quantity' => $the_order->order_lightweightitems_wash_and_iron_quantity,
                'order_lightweightitems_just_iron_quantity' => $the_order->order_lightweightitems_just_iron_quantity,
                'order_bulkyitems_just_wash_quantity' => $the_order->order_bulkyitems_just_wash_quantity,
                'order_bulkyitems_wash_and_iron_quantity' => $the_order->order_bulkyitems_wash_and_iron_quantity,
                'order_total_lightweight_items' => $the_order->order_lightweightitems_just_wash_quantity + $the_order->order_lightweightitems_wash_and_iron_quantity + $the_order->order_lightweightitems_just_iron_quantity,
                'order_total_bulkyweight_items' => $the_order->order_bulkyitems_just_wash_quantity + $the_order->order_bulkyitems_wash_and_iron_quantity,
                'order_total_items' => $the_order->order_lightweightitems_just_wash_quantity + $the_order->order_lightweightitems_wash_and_iron_quantity + $the_order->order_lightweightitems_just_iron_quantity + $the_order->order_bulkyitems_just_wash_quantity + $the_order->order_bulkyitems_wash_and_iron_quantity,
                'time' => date("F j, Y, g:i a")
            );
            Mail::to(config('app.supportemail'))->send(new NewOrderMailToAdmin($email_data));
    

            return response([
                "status" => "success", 
                "message" => "Order updated"
            ]);
        } else {
            if($request->purge){
                $the_order->delete();
                return response([
                    "status" => "success", 
                    "message" => "Order deleted"
                ]);
            }
        }
    }

    public function updateOrder(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }

        $validatedData = $request->validate([
            "order_id" => "bail|required|max:100",
            "new_status" => "bail|required|max:100",
            "admin_pin" => "bail|integer",
            "order_delivery_date" => "bail|max:100",
            "new_status_details" => "bail|max:200",
            "order_payment_status" => "bail|max:100",
            "order_payment_details" => "bail|max:200",
            "order_payment_method" => "bail|max:200",
            "order_delete" => "bail|max:1",
            "biker_name" => "bail|max:100",
            "biker_phone" => "bail|max:100",
            "order_all_items_full_description" => "bail|max:200",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        if(auth()->user()->user_id != 1 || $request->admin_pin != 6011) { // MESSAGE FROM ADMIN TO USER
            return response([
                "status" => "error", 
                "message" => "An unexpected error occurred"
            ]);
        }
        
        //echo "ID: " . strval(intval($request->order_id));exit;
        $the_order = Order::where('order_id', '=', strval(intval($request->order_id)))->first();
        if($the_order === null){
            return response([
                "status" => "error", 
                "message" => "Order not found"
            ]);
        }


        $user1 = User::where('user_id', '=', $the_order->order_user_id)->first();
        if(empty($user1->user_phone)){
            return response([
                "status" => "error", 
                "message" => "User who placed order not found"
            ]);
        }

        // PAYMENT INFO CHANGE
        if($the_order->order_status == 0 && intval($request->new_status) == 1){
            if(empty($request->order_payment_method) || empty($request->order_payment_details)){
                return response([
                    "status" => "error", 
                    "message" => "Make sure to fill in the payment method and payment details"
                ]);
            }
            if($request->order_payment_status == "approved"){
                $the_order->order_status = 1; // PENDING ASSIGNMENT TO PICKER SINCE PAYMENT IS MADE. 
                $the_order->order_payment_method = $request->order_payment_method;
                $the_order->order_payment_status = 1;
                $the_order->order_payment_details = $request->order_payment_details;
                $the_order->save();
                return response([
                    "status" => "success", 
                    "message" => "Order payment updated"
                ]);
            } else {
                if($request->order_delete){
                    $the_order->delete();
                    return response([
                        "status" => "success", 
                        "message" => "Order deleted for non payment"
                    ]);
                } else {
                    $the_order->order_status = 7;
                    $the_order->order_payment_method = $request->order_payment_method;
                    $the_order->order_payment_status = 2; // FAILED PAYMENT
                    $the_order->order_payment_details = $request->order_payment_details;
                    $the_order->save();
                }
            }
        } 
        // ASSIGNING PICKER
        else if($the_order->order_status == 1 && intval($request->new_status) == 2){
            if(empty($request->biker_name) || empty($request->biker_phone)){
                return response([
                    "status" => "error", 
                    "message" => "Make sure to fill in the biker name and biker phone number"
                ]);
            }
            $the_order->order_status = 2; // PICKER ASSIGNED GOING TO PICKUP. 
            $the_order->order_picker_name = $request->biker_name;
            $the_order->order_picker_phone = $request->biker_phone;
            $the_order->save();

            UtilController::addNotificationToUserQueue("Order assigned for pickup", "Your order has been assigned for pickup. Expect a picker at your location on time.", $user1->user_phone, 6011);
            UtilController::sendNotificationToUser($user1->user_notification_token_android,"normal","Order Pickup Assigned - MeMaww", "Your order has been assigned for pickup. Expect a picker at your location on time.");
            UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal","Order Pickup Assigned - MeMaww", "Your order has been assigned for pickup. Expect a picker at your location on time.");
        
            return response([
                "status" => "success", 
                "message" => "Order assigned for pickup"
            ]);
        }

        // PICKED UP
        else if($the_order->order_status == 2 && intval($request->new_status) == 3){
            if(empty($request->order_all_items_full_description)){
                return response([
                    "status" => "error", 
                    "message" => "Make sure to fill in full description of items picked up"
                ]);
            }
            $the_order->order_status = 3; // WASHING OR PICKED
            $the_order->order_all_items_full_description = $request->order_all_items_full_description; 
            $the_order->save();
            
            return response([
                "status" => "success", 
                "message" => "Order set to picked up"
            ]);
        }


        // WASHING
        else if($the_order->order_status == 3 && intval($request->new_status) == 4){
            $the_order->order_status = intval($request->new_status); // WASHING
            $the_order->save();

            UtilController::addNotificationToUserQueue("Laundry-In-Washing - MeMaww", "Your laundry is being washed.", $user1->user_phone, 6011);
            UtilController::sendNotificationToUser($user1->user_notification_token_android,"normal","Laundry-In-Washing - MeMaww", "Your laundry is being washed");
            UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal","Laundry-In-Washing - MeMaww", "Your laundry is being washed");


            return response([
                "status" => "success", 
                "message" => "Order set to washing"
            ]);
        }

        // ASSIGNING TO DELIVERER
        else if($the_order->order_status == 4 && intval($request->new_status) == 5){
            if(empty($request->biker_name) || empty($request->biker_phone)){
                return response([
                    "status" => "error", 
                    "message" => "Make sure to fill in the biker name and biker phone number"
                ]);
            }
            $the_order->order_status = 5; // DELIVERER ASSIGNED GOING TO DELIVER. 
            $the_order->order_deliverer_name = $request->biker_name;
            $the_order->order_deliverer_phone = $request->biker_phone;
            $the_order->save();

            UtilController::addNotificationToUserQueue("Delivery-On-Course - MeMaww", "Your laundry is being delivered.", $user1->user_phone, 6011);
            UtilController::sendNotificationToUser($user1->user_notification_token_android,"normal","Delivery-On-Course - MeMaww", "Your laundry is being delivered.");
            UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal","Delivery-On-Course - MeMaww", "Your laundry is being delivered.");

            return response([
                "status" => "success", 
                "message" => "Order assigned for delivery"
            ]);
        }


        // COMPLETING ORDER
        else if($the_order->order_status == 5 && intval($request->new_status) == 6){
            if(empty($request->order_delivery_date) || !UtilController::validateDate($request->order_delivery_date, 'Y-m-d H:i:s')){
                return response([
                    "status" => "error", 
                    "message" => "Make sure to fill in the order delivery date in the format " .  date('Y-m-d H:i:s'),
                ]);
            }
            $the_order->order_dropoff_date = $request->order_delivery_date;
            $the_order->order_status = 6;
            $the_order->save();

            UtilController::addNotificationToUserQueue("Order Completed", "Your order is completed.", $user1->user_phone, 6011);
            UtilController::sendNotificationToUser($user1->user_notification_token_android,"normal","Order Completed", "Your laundry is delivered and order completed. Use again. :-)");
            UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal","Order Completed", "Your laundry is delivered and order completed. Use again. :-)");

            return response([
                "status" => "success", 
                "message" => "Order completed"
            ]);
        }

        // OTHER STATUS
        else if($the_order->order_status == 0 && intval($request->new_status) == 7){ // CANCELLING ORDER
            if(empty($request->new_status_details)){
                return response([
                    "status" => "error", 
                    "message" => "Enter new status details"
                ]);
            }
            $the_order->order_status = 7; // DELIVERER ASSIGNED GOING TO DELIVER. 
            $the_order->order_status_details = $request->new_status_details;
            $the_order->save();


            UtilController::addNotificationToUserQueue("Order Cancelled", "Your order  has been cancelled.", $user1->user_phone, 6011);
            UtilController::sendNotificationToUser($user1->user_notification_token_android, "normal","Order Cancelled", "Your order  has been cancelled.");
            UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal","Order Cancelled", "Your order  has been cancelled.");


            return response([
                "status" => "success", 
                "message" => "Order status updated to cancelled with reason"
            ]);
        }

        // COMPLETING ORDER
        else {
            return response([
                "status" => "error", 
                "message" => "Set information right. Order status: " . $the_order->order_status_message
            ]);
        }

    }



/*
    public function confirmCollectionRequestOrder(Request $request){

        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    
        $validatedData = $request->validate([
            "collect_loc_raw" => "bail|required|max:100",
            "collect_loc_gps" => "bail|max:20",
            "collect_datetime" => "bail|required|date_format:H:i",
            "contact_person_phone" => "bail|required|max:10",
            "drop_loc_raw" => "bail|max:100",
            "drop_loc_gps" => "bail|max:20",
            "drop_datetime" => "bail|max:12",
            "smallitems_justwash_quantity" => "bail|required|integer|digits_between:-1,1000",
            "smallitems_washandiron_quantity" => "bail|required|integer|digits_between:-1,1000",
            "smallitems_justiron_quantity" => "bail|required|integer|digits_between:-1,1000",
            "bigitems_justwash_quantity" => "bail|required|integer|digits_between:-1,1000",
            "bigitems_washandiron_quantity" => "bail|required|integer|digits_between:-1,1000",
            "discount_code" => "bail|max:12",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
        
        // REMEMBER TO GIVE REFERROR'S THEIR DISCOUNT AS A MESSAGE AND NOTIFICATION
        // WHEN THEIR INVITEES PLACE THEIR FIRST ORDER
    }
*/
    public function requestCollectionCallBack(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    
        $validatedData = $request->validate([
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        $latest_callback_req = CollectionCallBackRequest::where("col_callback_req_user_id", auth()->user()->user_id)->orderBy('col_callback_req_id', 'DESC')->first();;

        if(!empty($latest_callback_req->created_at)){
            if(intval(UtilController::getTimePassed(date("Y-m-d H:i:s"), $latest_callback_req->created_at)) < 30){
                return response([
                    "status" => "success", 
                    "message" => "Your previous callback request is in the works. You should receive a callback shortly"
                ]);
            }
        }

        $collectionRequestData["col_callback_req_user_id"] = auth()->user()->user_id;
        $collectionRequestData["col_callback_req_status"] = 0;
        $collectionRequestData["col_callback_req_status_message"] = "User not called";
        $order = CollectionCallBackRequest::create($collectionRequestData);
    
        $email_data = array(
            'message_text' => 'Please call me to take my order. I need laundry collection.',
            'user_name' => auth()->user()->user_first_name . " " . auth()->user()->user_last_name,
            'user_phone' => auth()->user()->user_phone,
            'time' => date("F j, Y, g:i a")
        );
        Mail::to(config('app.supportemail'))->send(new RequestCollectionCallbackMailToAdmin($email_data));

        return response([
            "status" => "success", 
            "message" => "We will call you shortly to take your order. Thank you."
        ]);
    
    }
    

    public function getMyOrdersListing(Request $request)
    {
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    

        // MAKING SURE THE INPUT HAS THE EXPECTED VALUES
        $validatedData = $request->validate([
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
    
        //$customer_item_detail_data = Order::where("order_user_id", auth()->user()->user_id)->orderBy('order_id','desc')->get();
        $customer_item_detail_data = Order::where("order_user_id", auth()->user()->user_id)->whereNot('order_status','<=>',0)->orderBy('order_id','desc')->get();

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "data" => $customer_item_detail_data
        ]);
    }


    public function sendMessage(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    
        $validatedData = $request->validate([
            "message" => "bail|required|max:1000",
            "receiver_id" => "bail|integer",
            "admin_pin" => "bail|integer",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        if(auth()->user()->user_id == 1) { // MESSAGE FROM ADMIN TO USER
            if($request->admin_pin != 6011){
                return response([
                    "status" => "error", 
                    "message" => "Incorrect Admin PIN"
                ]);
            }
            $message["message_text"] = $request->message;
            $message["message_sender_user_id"] = 1;
            $message["message_receiver_id"] = $request->receiver_id;
            $message = Message::create($message);

            $user1 = User::where('user_id', '=', $request->receiver_id)->first();
            if(!empty($user1->user_phone)){

                UtilController::addNotificationToUserQueue("New Message - MeMaww", "You have a response from MeMaww Support", $user1->user_phone, 6011);
                UtilController::sendNotificationToUser($user1->user_notification_token_android, "normal","New Message - MeMaww", "You have a response from MeMaww Support");
                UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal","New Message - MeMaww", "You have a response from MeMaww Support");
    
            }

        } else {

            $last_3_messages_data = Message::where("message_sender_user_id", auth()->user()->user_id)->orWhere('message_receiver_id', auth()->user()->user_id)->orderBy('message_id','desc')->take(3)->get();

            
            if(
                count($last_3_messages_data) == 3
                && 
                (
                $last_3_messages_data[0]->message_sender_user_id === auth()->user()->user_id 
                && $last_3_messages_data[1]->message_sender_user_id === auth()->user()->user_id
                && $last_3_messages_data[2]->message_sender_user_id === auth()->user()->user_id
                )
                && intval(UtilController::getTimePassed($last_3_messages_data[0]->created_at, date("Y-m-d H:i:s"))) < 30
            ){

                return response([
                    "status" => "error", 
                    "message" => "Please wait for a response or try sending your message 30mins time later. You can also call us on +233535065535"
                ]);
            }
            
            

            $message["message_text"] = $request->message;
            $message["message_sender_user_id"] = auth()->user()->user_id;
            $message["message_receiver_id"] = 1;
            $message = Message::create($message);
    
            $email_data = array(
                'message_text' => $request->message,
                'user_name' => auth()->user()->user_first_name . " " . auth()->user()->user_last_name,
                'user_id' => auth()->user()->user_id,
                'user_phone' => auth()->user()->user_phone,
                'time' => date("F j, Y, g:i a")
            );
            Mail::to(config('app.supportemail'))->send(new GeneralMailToAdmin($email_data));
        }

        return response([
            "status" => "success", 
            "message" => "Message sent", 
            "data" => $message
        ]);
    
    }


    public function getMyMessages(Request $request)
    {
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    

        // MAKING SURE THE INPUT HAS THE EXPECTED VALUES
        $validatedData = $request->validate([
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
    
        $customer_item_detail_data = Message::where("message_sender_user_id", auth()->user()->user_id)->orWhere('message_receiver_id', auth()->user()->user_id)->orderBy('message_id','desc')->take(71)->get();

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "data" => $customer_item_detail_data
        ]);
    }

    public function sendNotification(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }

        $validatedData = $request->validate([
            "title" => "bail|required|max:50",
            "short_body" => "bail|required|max:100",
            "body" => "bail|required|max:1000",
            "topic_or_receiver_phone" => "bail|required|max:15",
            "admin_pin" => "bail|integer",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        if($request->admin_pin == 6011) { // MESSAGE FROM ADMIN TO USER
            UtilController::addNotificationToUserQueue($request->title, $request->body, $request->topic_or_receiver_phone, $request->admin_pin);
            if($request->topic_or_receiver_phone == "ALL_USERS" || $request->topic_or_receiver_phone == "ANDROID_USERS" || $request->topic_or_receiver_phone == "IPHONE_USERS"){
                UtilController::sendNotificationToTopic($request->topic_or_receiver_phone,"normal",$request->title, $request->short_body);
            } else { // SPECIFIC USERS
                $user1 = User::where('user_phone', '=', $request->topic_or_receiver_phone)->first();
                if(!empty($user1->user_phone)){
                    UtilController::sendNotificationToUser($user1->user_notification_token_android,"normal",$request->title, $request->short_body);
                    UtilController::sendNotificationToUser($user1->user_notification_token_ios,"normal",$request->title, $request->short_body);
                }
            }

            return response([
                "status" => "success", 
                "message" => "Notification sent"
            ]);
        } 

        return response([
            "status" => "error", 
            "message" => "An unexpected error occurred"
        ]);
    
    }


    public function getMyNotificationsListing(Request $request)
    {
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    

        // MAKING SURE THE INPUT HAS THE EXPECTED VALUES
        $validatedData = $request->validate([
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
    
        $customer_item_detail_data = Notification::where("notification_topic_or_receiver_phone", auth()->user()->user_phone)->orWhere('notification_topic_or_receiver_phone', "ALL_USERS")->orderBy('notification_id','desc')->take(50)->get();

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "data" => $customer_item_detail_data
        ]);
    }


    public function updateUserInfo(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again", "subscription_set" => false]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted", "subscription_set" => false]);
        }
    
        $validatedData = $request->validate([
            "fcm_token" => "bail|required|max:1000",
            "fcm_type" => "bail|required|max:7",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);


        $user1 = User::where('user_id', '=', auth()->user()->user_id)->first();

        if($user1 === null){
            return response([
                "status" => "error", 
                "message" => "User not found", 
                "subscription_set" => false
            ]);
        }

        $user_subscription = Subscription::where('subscription_id', '=', $user1->subscription_id)->first();
        $subscription_set = false;


        
        /*
        echo ((UtilController::getTimePassed($user_subscription->created_at, date('Y-m-d H:i:s'))/(60*24))/30);
        
        echo "\n\n<br><br> SUB DATE: " . $user_subscription->created_at;
        
        echo "\n\n<br><br> NOW: " . date('Y-m-d H:i:s');
        
        echo "\n\n<br><br> SUB DATE: " . $user_subscription->subscription_payment_transaction_id;

        echo "\n\n<br><br> " . $user_subscription->subscription_number_of_months; exit;
        */

        

        if(
            !empty($user_subscription->subscription_payment_transaction_id) 
            && ((UtilController::getTimePassed($user_subscription->created_at, date('Y-m-d H:i:s'))/(60*24))/30) < $user_subscription->subscription_number_of_months
            && $user_subscription->subscription_pickups_done < 4){
            $subscription_set = true;
        } else {
            $subscription_set = false;
            $user_subscription = null;
        }

        if($request->fcm_type == "ANDROID"){
            $user1->user_notification_token_android = $request->fcm_token;
            $user1->save();
        } else if($request->fcm_type == "IPHONE"){
            $user1->user_notification_token_ios = $request->fcm_token;
            $user1->save();
        } else {
            return response([
                "status" => "error", 
                "message" => "FCM type unknown",
                "subscription_set" => $subscription_set,
                "subscription" => $user_subscription
            ]);
        }

        if(strtoupper($request->app_type) == "ANDROID"){

            return response([
                "status" => "success", 
                "min_vc" => config('app.androidminvc'), 
                "message" => "Success",
                "subscription_set" => $subscription_set,
                "subscription" => $user_subscription
            ]);

        } else if(strtoupper($request->app_type) == "IOS"){

            return response([
            "status" => "error", 
            "min_vc" => config('app.iosminvc'), 
            "message" => "Success",
            "subscription_set" => $subscription_set,
            "subscription" => $user_subscription
            ]);

        } else {
            return response([
                "status" => "error", 
                "message" => "Device unknown",
                "subscription_set" => $subscription_set
            ]);
        }

    
    }


    function getSubscriptionPricing(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }
    

        // MAKING SURE THE INPUT HAS THE EXPECTED VALUES
        $validatedData = $request->validate([
            "subscription_max_number_of_people_in_home" => "bail|required|integer",
            "subscription_number_of_months" => "bail|required|integer|digits_between:0,13",
            "subscription_pickup_time" => "bail|required|max:5",
            "subscription_pickup_location" => "bail|required|max:100",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        
        $subscription_country = Country::where('country_id', '=', auth()->user()->user_country_id)->first();

        if(empty($subscription_country->country_currency_symbol)){
            return response([
                "status" => "error", 
                "message" => "User currency not found"
            ]);
        }
        /*
        $last_subscription = Subscription::orderBy('created_at', 'desc')->first();
        if(empty($subscription_country->country_currency_symbol)){
            return response([
                "status" => "error", 
                "message" => "User currency not found"
            ]);
        }
        */
        $subscription_id = sprintf("%012d", auth()->user()->user_id . date('ymdis'));

        $subs_index = "sub_" . $request->subscription_max_number_of_people_in_home . "_ppl_" . $request->subscription_number_of_months . "month";

        $offers_array = [
            "sub_1_ppl_1month" => strval(217*1), // 0% off
            "sub_2_ppl_1month" => strval(305*1), // 15% off
            "sub_3_ppl_1month" => strval(372*1), // 12% off
            "sub_4_ppl_1month" => strval(563*1), // 12% off
            "sub_5_ppl_1month" => strval(754*1), // 12% off
            "sub_6_ppl_1month" => strval(945*1), // 12% off  (217 * number of people) - 200
            "sub_7_ppl_1month" => strval(1136*1), // 12% off (217 * number of people) - 200
            "sub_8_ppl_1month" => strval(1310*1), // 13% off  (217 * number of people) - 200
            "sub_9_ppl_1month" => strval(1479*1), // 14% off (217 * number of people) - 200
            "sub_10_ppl_1month" => strval(1844*1), // 15% off (217 * number of people) - 200


            "sub_1_ppl_3month" => strval(217*3*0.85), // 0% off
            "sub_2_ppl_3month" => strval(305*3*0.85), // 15% off
            "sub_3_ppl_3month" => strval(372*3*0.85), // 12% off
            "sub_4_ppl_3month" => strval(563*3*0.85), // 12% off
            "sub_5_ppl_3month" => strval(754*3*0.85), // 12% off
            "sub_6_ppl_3month" => strval(945*3*0.85), // 12% off  (217 * number of people) - 200
            "sub_7_ppl_3month" => strval(1136*3*0.85), // 12% off (217 * number of people) - 200
            "sub_8_ppl_3month" => strval(1310*3*0.85), // 13% off  (217 * number of people) - 200
            "sub_9_ppl_3month" => strval(1479*3*0.85), // 14% off (217 * number of people) - 200
            "sub_10_ppl_3month" => strval(1844*3*0.85), // 15% off (217 * number of people) - 200


            "sub_1_ppl_6month" => strval(217*6*0.75), // 0% off
            "sub_2_ppl_6month" => strval(305*6*0.75), // 15% off
            "sub_3_ppl_6month" => strval(372*6*0.75), // 12% off
            "sub_4_ppl_6month" => strval(563*6*0.75), // 12% off
            "sub_5_ppl_6month" => strval(754*6*0.75), // 12% off
            "sub_6_ppl_6month" => strval(945*6*0.75), // 12% off  (217 * number of people) - 200
            "sub_7_ppl_6month" => strval(1136*6*0.75), // 12% off (217 * number of people) - 200
            "sub_8_ppl_6month" => strval(1310*6*0.75), // 13% off  (217 * number of people) - 200
            "sub_9_ppl_6month" => strval(1479*6*0.75), // 14% off (217 * number of people) - 200
            "sub_10_ppl_6month" => strval(1844*6*0.75), // 15% off (217 * number of people) - 200

            "sub_1_ppl_12month" => strval(217*12*0.7), // 0% off
            "sub_2_ppl_12month" => strval(305*12*0.7), // 15% off
            "sub_3_ppl_12month" => strval(372*12*0.7), // 12% off
            "sub_4_ppl_12month" => strval(563*12*0.7), // 12% off
            "sub_5_ppl_12month" => strval(754*12*0.7), // 12% off
            "sub_6_ppl_12month" => strval(945*12*0.7), // 12% off  (217 * number of people) - 200
            "sub_7_ppl_12month" => strval(1136*12*0.7), // 12% off (217 * number of people) - 200
            "sub_8_ppl_12month" => strval(1310*12*0.7), // 13% off  (217 * number of people) - 200
            "sub_9_ppl_12month" => strval(1479*12*0.7), // 14% off (217 * number of people) - 200
            "sub_10_ppl_12month" => strval(1844*12*0.7), // 15% off (217 * number of people) - 200
        ];

        if(!array_key_exists($subs_index, $offers_array)){
            return response([
                "status" => "error", 
                "message" => "No offer exists for the number of people or months selected. Choose something else."
            ]);
        }
        
        $subscriptionData["subscription_items_washed"] = 0;
        $subscriptionData["subscription_pickups_done"] = 0;
        $subscriptionData["subscription_pickup_day"] = "Saturday";
        $subscriptionData["subscription_payment_transaction_id"] = $subscription_id;
        $subscriptionData["subscription_payment_response"] = "pending";
        $subscriptionData["subscription_amount_paid"] = $offers_array[$subs_index];
        $subscriptionData["subscription_max_number_of_people_in_home"] = $validatedData["subscription_max_number_of_people_in_home"];
        $subscriptionData["subscription_number_of_months"] = $validatedData["subscription_number_of_months"];
        $subscriptionData["subscription_pickup_time"] = $validatedData["subscription_pickup_time"];
        $subscriptionData["subscription_pickup_location"] = $validatedData["subscription_pickup_location"];
        $subscriptionData["subscription_package_description"] = ""; 
        $subscriptionData["subscription_country_id"] = auth()->user()->user_country_id;
        $subscriptionData["subscription_user_id"] = auth()->user()->user_id;
        Subscription::create($subscriptionData);

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "subscription_id" => $subscription_id, 
            "user_email" => auth()->user()->user_phone . "@memaww.com", 
            "txn_info" => $request->subscription_max_number_of_people_in_home . " person(s) in-houselhold for " . $request->subscription_number_of_months . " months", 
            "txn_narration" => "Laundry subscription by " . auth()->user()->user_last_name . " " . auth()->user()->user_first_name, 
            "txn_reference" => $subscription_id, 
            "merchant_id" => config('app.payment_gateway_merchant_id'), 
            "merchant_api_user" => config('app.payment_gateway_merchant_api_user'), 
            "merchant_api_key" => config('app.payment_gateway_merchant_api_key'), 
            "merchant_test_api_key" => config('app.payment_gateway_merchant_test_api_key'), 
            "return_url" => config('app.url') . "/subscription/" . $subscription_id, 
            "currency_symbol" => $subscription_country->country_currency_symbol, 
            "subscription_country_id" => strval(auth()->user()->user_country_id), 
            "subscription_price" => $offers_array[$subs_index],
            "packageinfo1" => "1 pickup and delivery per week", 
            "packageinfo2" => "Unlimited items", 
            "packageinfo3" => "Wash & Fold/Iron",
            "packageinfo4" => "Delivery in 48hrs", 
        ]);

    }

    
    public function setUserSubscription(Request $request){
        if (!Auth::guard('api')->check() || !$request->user()->tokenCan("use-mobile-apps-as-normal-user")) {
            return response(["status" => "fail", "message" => "Permission Denied. Please log out and login again"]);
        }

        if (auth()->user()->user_flagged) {
            $request->user()->token()->revoke();
            return response(["status" => "fail", "message" => "Account access restricted"]);
        }

        $validatedData = $request->validate([
            "subscription_payment_transaction_id" => "bail|required|max:1000",
            "subscription_amount_paid" => "bail|required|integer",
            "subscription_max_number_of_people_in_home" => "bail|required|integer",
            "subscription_number_of_months" => "bail|required|integer|digits_between:0,13",
            "subscription_pickup_time" => "bail|required|max:5",
            "subscription_pickup_location" => "bail|required|max:100",
            "subscription_package_description" => "bail|required|max:100",
            "subscription_country_id" => "bail|required|integer",
            "subscription_purge" => "bail|required|integer",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        $user1 = User::where('user_id', '=', auth()->user()->user_id)->first();

        if($user1 === null){
            return response([
                "status" => "error", 
                "message" => "User not found"
            ]);
        }

        $this_subscription = Subscription::where('subscription_payment_transaction_id', '=', $request->subscription_payment_transaction_id)->first();

        if($this_subscription === null){
            return response([
                "status" => "error", 
                "message" => "Subscription pre-record not found"
            ]);
        }

        if ($request->subscription_purge) {
                $this_subscription->delete();
                return response([
                    "status" => "success", 
                    "message" => "Subscription pre-record deleted"
                ]);
        }

        if($request->subscription_max_number_of_people_in_home < 1 || $request->subscription_max_number_of_people_in_home > 10) {
            return response([
                "status" => "error", 
                "message" => "The number of people must be from 1 to 10"
            ]);
        }

        $payment_verify = UtilController::verifyPayStackTransaction($request->subscription_payment_transaction_id);
        if($payment_verify->status != "approved") {
            return response([
                "status" => "error", 
                "message" => "Payment verification failed"
            ]);
        }

        if(
            ($request->subscription_max_number_of_people_in_home == "1" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(0.10))
            || ($request->subscription_max_number_of_people_in_home == "2" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(305*1))
            || ($request->subscription_max_number_of_people_in_home == "3" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(372*1))
            || ($request->subscription_max_number_of_people_in_home == "4" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(563*1))
            || ($request->subscription_max_number_of_people_in_home == "5" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(754*1))
            || ($request->subscription_max_number_of_people_in_home == "6" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(945*1))
            || ($request->subscription_max_number_of_people_in_home == "7" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(1136*1))
            || ($request->subscription_max_number_of_people_in_home == "8" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(1310*1))
            || ($request->subscription_max_number_of_people_in_home == "9" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(1479*1))
            || ($request->subscription_max_number_of_people_in_home == "10" && $request->subscription_number_of_months == "1" && $payment_verify->amount != strval(1844*1))

            || ($request->subscription_max_number_of_people_in_home == "1" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(217*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "2" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(305*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "3" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(372*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "4" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(563*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "5" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(754*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "6" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(945*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "7" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(1136*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "8" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(1310*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "9" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(1479*3*0.85))
            || ($request->subscription_max_number_of_people_in_home == "10" && $request->subscription_number_of_months == "3" && $payment_verify->amount != strval(1844*3*0.85))

            || ($request->subscription_max_number_of_people_in_home == "1" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(217*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "2" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(305*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "3" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(372*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "4" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(563*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "5" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(754*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "6" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(945*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "7" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(1136*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "8" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(1310*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "9" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(1479*6*0.75))
            || ($request->subscription_max_number_of_people_in_home == "10" && $request->subscription_number_of_months == "6" && $payment_verify->amount != strval(1844*6*0.75))

            || ($request->subscription_max_number_of_people_in_home == "1" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(217*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "2" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(305*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "3" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(372*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "4" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(563*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "5" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(754*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "6" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(945*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "7" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(1136*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "8" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(1310*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "9" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(1479*12*0.7))
            || ($request->subscription_max_number_of_people_in_home == "10" && $request->subscription_number_of_months == "12" && $payment_verify->amount != strval(1844*12*0.7))
            ){
                return response([
                    "status" => "error", 
                    "message" => "Payment inconsistency detected. Your payment will be investigated and refunded."
                ]);
        }

        /*
        $subscriptionData["subscription_items_washed"] = 0;
        $subscriptionData["subscription_pickups_done"] = 0;
        $subscriptionData["subscription_pickup_day"] = "Saturday";
        $subscriptionData["subscription_payment_transaction_id"] = $validatedData["subscription_payment_transaction_id"];
        $subscriptionData["subscription_payment_response"] = $payment_verify->reason;
        $subscriptionData["subscription_amount_paid"] = $validatedData["subscription_amount_paid"];
        $subscriptionData["subscription_max_number_of_people_in_home"] = $validatedData["subscription_max_number_of_people_in_home"];
        $subscriptionData["subscription_number_of_months"] = $validatedData["subscription_number_of_months"];
        $subscriptionData["subscription_pickup_time"] = $validatedData["subscription_pickup_time"];
        $subscriptionData["subscription_pickup_location"] = $validatedData["subscription_pickup_location"];
        $subscriptionData["subscription_package_description"] = $validatedData["subscription_package_description"]; 
        $subscriptionData["subscription_country_id"] = $validatedData["subscription_country_id"];
        $subscriptionData["subscription_user_id"] = auth()->user()->user_id;
        $this_subscription = Subscription::create($subscriptionData);
        */
        $this_subscription->subscription_payment_response = $payment_verify->reason;
        $this_subscription->subscription_package_description = $request->subscription_package_description;
        $this_subscription->save();
        $user1->subscription_id = $this_subscription->subscription_id;
        $user1->save();

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "subscription" => $this_subscription
        ]);
    
    }


}
