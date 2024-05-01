<?php

namespace App\Http\Controllers\version1;


use App\Models\version1\User;
use App\Http\Controllers\Controller;
use App\Mail\version1\GeneralMailToAdmin;
use Illuminate\Support\Facades\Mail;
use App\Mail\version1\RequestCollectionCallbackMailToAdmin;
use App\Models\version1\CollectionCallBack;
use App\Models\version1\CollectionCallBackRequest;
use App\Models\version1\Country;
use App\Models\version1\Discount;
use App\Models\version1\Message;
use App\Models\version1\Order;
use Illuminate\Http\Request;
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
            $userData["user_sys_id"] = date("Y-m-d-H-i-s") . UtilController::getRandomString(91);
            $userData["user_first_name"] = $request->user_first_name;
            $userData["user_last_name"] = $request->user_last_name;
            $userData["user_phone"] = $user_phone_correct;
            $userData["user_country_id"] = $user_country->country_id;
            $userData["user_referral_code"] = substr(uniqid(),-10);
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
        }

        $user1 = User::with('userCountry')->where("user_id", $user1->user_id)->latest()->first();

        $accessToken = $user1->createToken("authToken", ["use-mobile-apps-as-normal-user"])->accessToken;

        return response([
            "status" => "success", 
            "message" => "Sign-in successful",
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
            "special_instructions" => "bail|max:2000",
            "discount_code" => "bail|max:12",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
        
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

        if(auth()->user()->user_country_id == 81) { // GHANA

            // LIGHT WEIGHT ITEMS --- WASH AND FOLD
            if($request->smallitems_justwash_quantity < 10 && $request->smallitems_justwash_quantity > 0){
                $final_price = $final_price + 70;
            } else if($request->smallitems_justwash_quantity >= 10 && $request->smallitems_justwash_quantity < 15){
                $final_price = $final_price + ($request->smallitems_justwash_quantity * 7);
            } else {
                $final_price = $final_price + ($request->smallitems_justwash_quantity * 5);
            }

            // LIGHT WEIGHT ITEMS --- WASH AND IRON
            if($request->smallitems_washandiron_quantity < 10 && $request->smallitems_washandiron_quantity > 0 && $final_price < 70){
                $final_price = $final_price + 100;
            } else if($request->smallitems_washandiron_quantity >= 10 && $request->smallitems_washandiron_quantity < 15 && $final_price < 70){
                $final_price = $final_price + ($request->smallitems_washandiron_quantity * 10);
            } else {
                $final_price = $final_price + ($request->smallitems_washandiron_quantity * 8);
            }

            // LIGHT WEIGHT ITEMS --- JUST IRON
            if($request->smallitems_justiron_quantity < 10 && $request->smallitems_justiron_quantity > 0 && $final_price < 70){
                $final_price = $final_price + 70;
            } else if($request->smallitems_justiron_quantity >= 10 && $request->smallitems_justiron_quantity < 15 && $final_price < 70){
                $final_price = $final_price + ($request->smallitems_justiron_quantity * 6);
            } else {
                $final_price = $final_price + ($request->smallitems_justiron_quantity * 5);
            }
            
            // BULKY ITEMS --- WASH AND FOLD
            if($request->bigitems_justwash_quantity == 1 && $final_price < 70){
                $final_price = $final_price + 50;
            } else if($request->bigitems_justwash_quantity >= 2 && $request->bigitems_justwash_quantity < 5 && $final_price < 70){
                $final_price = $final_price + ($request->bigitems_justwash_quantity * 35);
            } else {
                $final_price = $final_price + ($request->bigitems_justwash_quantity * 25);
            }
             
            // BULKY ITEMS --- WASH AND IRON
            if($request->bigitems_washandiron_quantity == 1 && $final_price < 70){
                $final_price = $final_price + 80;
            } else if($request->bigitems_washandiron_quantity >= 2 && $request->bigitems_washandiron_quantity < 5 && $final_price < 70){
                $final_price = $final_price + ($request->bigitems_washandiron_quantity * 40);
            } else {
                $final_price = $final_price + ($request->bigitems_washandiron_quantity * 30);
            }

            $original_price = $final_price;
            if(!empty($request->discount_code) && $final_price > 0){
                $discount = Discount::where('discount_code', '=', $request->discount_code)->first();
                //var_dump($discount); exit;
                if(!empty($discount->discount_percentage) && $discount->discount_percentage > 0 && (empty($discount->discount_restricted_to_user_id) || ($discount->discount_restricted_to_user_id == auth()->user()->user_id))){
                    $discount_id = $discount->discount_id;
                    $discount_percentage =  $discount->discount_percentage;
                    $discount_amount = $final_price * (($discount->discount_percentage)/100);
                    $discount_amount_usd = $discount_amount/config('app.one_dollar_to_one_ghana_cedi');
                    $final_price =  $final_price * ((100-$discount->discount_percentage)/100);
                }
            }

            $final_price = strval($final_price);
            
        } else {
            return response([
                "status" => "error", 
                "message" => "Service not available in your country."
            ]);
        }
        $orderData["order_sys_id"] = auth()->user()->user_id . "_" . date("YmdHis") . UtilController::getRandomString(4);
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
        $orderData["special_instructions"] = $validatedData["special_instructions"];

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
            "discount_percentage" => $userCountry->country_currency_symbol . strval($discount_percentage), 
            "discount_amount" => $userCountry->country_currency_symbol . strval($discount_amount), 
            "price_final" => $userCountry->country_currency_symbol . strval($final_price), 
            "message" => "Order created"
        ]);
    
    }
    

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
            intval(UtilController::getTimePassed(date("Y-m-d h:i:sa"), $latest_callback_req->created_at)) < 30;
            return response([
                "status" => "success", 
                "message" => "Your previous callback request is in the works. You should receive a callback shortly"
            ]);
        }

        $collectionRequestData["col_callback_req_user_id"] = auth()->user()->user_id;
        $collectionRequestData["col_callback_req_status"] = 0;
        $collectionRequestData["col_callback_req_status_message"] = "User not called";
        $order = CollectionCallBackRequest::create($collectionRequestData);
    
        $email_data = array(
            'message_text' => 'This user has requested a laundry collection request from the MeMaww Team',
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
    
        $customer_item_detail_data = Order::where("order_user_id", auth()->user()->user_id)->orderBy('order_id','desc')->get();

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
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);

        if(auth()->user()->user_id == 1) { // MESSAGE FROM ADMIN TO USER
            $message["message_text"] = $request->message;
            $message["message_sender_user_id"] = 1;
            $message["message_receiver_id"] = $request->receiver_id;
            $message = Message::create($message);

            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
            // SEND NOTIFICATION TO USER HERE
        } else {
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
            "message" => "Message sent"
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
    
        $customer_item_detail_data = Message::where("message_sender_user_id", auth()->user()->user_id)->orWhere('message_receiver_id', auth()->user()->user_id)->orderBy('message_id','asc')->take(50)->get();

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "data" => $customer_item_detail_data
        ]);
    }


}
