<?php

namespace App\Http\Controllers\version1;


use App\Models\version1\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\version1\RequestCollectionCallbackMailToAdmin;
use App\Models\version1\CollectionCallBack;
use App\Models\version1\CollectionCallBackRequest;
use App\Models\version1\Country;
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

        $accessToken = $user1->createToken("authToken", ["use-mobile-apps-as-normal-user"])->accessToken;
        return response([
            "status" => "success", 
            "message" => "Sign-in successful",
            "access_token" => $accessToken,
            "user" => $user1,
        ]);

    }

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
            "collect_datetime" => "bail|required|date_format:Y-m-d H:i:s",
            "contact_person_phone" => "bail|required|max:10",
            "drop_loc_raw" => "bail|max:100",
            "drop_loc_gps" => "bail|max:20",
            "drop_datetime" => "bail|max:12",
            "smallitems_justwash_quantity" => "bail|required|integer|digits_between:-1,1000",
            "smallitems_washandiron_quantity" => "bail|required|integer|digits_between:-1,1000",
            "bigitems_justwash_quantity" => "bail|required|integer|digits_between:1,1000",
            "bigitems_washandiron_quantity" => "bail|required|integer|digits_between:-1,1000",
            "app_type" => "bail|required|max:8",
            "app_version_code" => "bail|required|integer"
        ]);
        
        $orderData["order_sys_id"] = auth()->user()->user_id . "_" . date("YmdHis") . UtilController::getRandomString(4);
        $orderData["order_user_id"] = auth()->user()->user_id;
        $orderData["order_laundrysp_id"] = 1; // MeMaww Ghana
        //$orderData["order_collection_biker_name"] = "";
        $orderData["order_collection_location_raw"] = $validatedData["collect_loc_raw"];
        $orderData["order_collection_location_gps"] = $validatedData["collect_loc_gps"];
        $orderData["order_collection_date"] = $validatedData["collect_datetime"];
        $orderData["order_collection_contact_person_phone"] = $validatedData["contact_person_phone"];
        $orderData["order_dropoff_location_raw"] = $validatedData["collect_loc_raw"];
        $orderData["order_dropoff_location_gps"] = $validatedData["collect_loc_gps"];
        $orderData["order_dropoff_date"] = $validatedData["drop_datetime"];
        $orderData["order_dropoff_contact_person_phone"] = $validatedData["contact_person_phone"];
        //$orderData["order_dropoff_biker_name"] = "";
        $orderData["order_lightweightitems_just_wash_quantity"] = $validatedData["smallitems_justwash_quantity"];
        $orderData["order_lightweightitems_wash_and_iron_quantity"] = $validatedData["smallitems_washandiron_quantity"];
        $orderData["order_bulkyitems_just_wash_quantity"] = $validatedData["bigitems_justwash_quantity"];
        $orderData["order_bulkyitems_wash_and_iron_quantity"] = $validatedData["bigitems_washandiron_quantity"];
        $orderData["order_being_worked_on_status"] = 0; //0-pending, 1-asignedForCollection, 2-Collected, 3-Washing, 4-assignedForDelivery, 5-completed
        $orderData["order_payment_status"] = 0; //0-pending, 1-paid-to-biker, 2-momo
        $orderData["order_payment_details"] = "";
        $orderData["order_flagged"] = false;
        $orderData["order_flagged_reason"] = "";
        $order = Order::create($orderData);
    
    
        return response([
            "status" => "success", 
            "message" => "Order created"
        ]);
    
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
            "message" => "Callback request order created"
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
    
        //$result = StockOwnership::where("user_id", auth()->user()->user_id)->Where('stockownership_stocks_quantity', '>', 0)->get();
        //$customer_item_detail_data = User::with('userOrders')->where("user_id", auth()->user()->user_id)->orderBy('order_id','desc')->get();

        $customer_item_detail_data = Order::with('orderDetails')->where("order_user_id", auth()->user()->user_id)->orderBy('order_id','desc')->get();
    
        /*
        $found_transactions = DB::table('transactions')
        ->select('transactions.transaction_referenced_item_id')
        ->where($where_array)
        ->orderBy('created_at', 'desc')
        //->take(100)
        ->get();

        $found_books = array();
        for ($i=0; $i < count($found_transactions); $i++) { 

            $where_array2 = array(
                ['books.book_sys_id', '=', $found_transactions[$i]->transaction_referenced_item_id],
                ['books.booksummary_flagged', '=', 0]
            ); 
            
            $this_book = DB::table('books')
            ->select('books.book_id', 'books.book_cover_photo', 'books.book_sys_id', 'books.book_title', 'books.book_author', 'books.book_ratings', 'books.book_description_short', 'books.book_description_long', 'books.book_pages', 'books.book_pdf', 'books.book_summary_pdf', 'books.book_audio', 'books.book_summary_audio', 'books.book_cost_usd', 'books.book_summary_cost_usd', 'books.bookfull_flagged', 'books.booksummary_flagged')
            ->where($where_array2)
            ->orderBy('created_at', 'desc')
            //->take(100)
            ->get();
            
    
            if(!empty($this_book[0]->book_sys_id)){
                if(!empty($this_book[0]->book_cover_photo) && file_exists(public_path() . "/uploads/books_cover_arts/" . $this_book[0]->book_cover_photo)){
                    $this_book[0]->book_cover_photo = config('app.books_cover_arts_folder') . "/" . $this_book[0]->book_cover_photo;
                } else {
                    $this_book[0]->book_cover_photo = config('app.books_cover_arts_folder') . "/sample_cover_art.jpg";
                }
                $this_book[0]->book_reference_url = config('app.url') . "/buy?ref=" . $this_book[0]->book_sys_id;

                array_push($found_books, $this_book[0]);
            }

        }
    */

        return response([
            "status" => "success", 
            "message" => "Operation successful", 
            "data" => $customer_item_detail_data
        ]);
    }



}
