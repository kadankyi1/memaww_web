<?php

namespace App\Http\Controllers\version1;

use DateTime;
use Illuminate\Http\Request;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use App\Http\Controllers\Controller;
use App\Models\version1\Discount;
use App\Models\version1\LaundryServiceProvider;
use App\Models\version1\Notification;

class UtilController extends Controller
{
    
    // GENERATE LOGIN CODE
    public static function generate_passcode()
    {
        return rand(10000,99999);
    }


    public static function checkUserAppVersionCode($app_type, $app_version_code)
    {

        if (strtoupper($app_type) == "ANDROID" && 
            (intval($app_version_code) < intval(config('app.androidminvc')) || $app_version_code > intval(config('app.androidmaxvc')) ) 
        ) {
            return response(["status" => "fail", "message" => "Please update your app from the Google Play Store", "subscription_set" => false]);
        } else if (strtoupper($app_type) == "IOS" && 
            (intval($app_version_code) < intval(config('app.iosminvc')) || $app_version_code > intval(config('app.iosmaxvc')) ) 
        ) {
            //$user()->token()->revoke();
            return response(["status" => "fail", "message" => "Please update your app from the App Store", "subscription_set" => false]);
        }

    }

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION GENERATES A RANDOM STRING
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
	public static function getRandomString($length) 
    {
		$str = "";
		$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
		$max = count($characters) - 1;
		for ($i = 0; $i < $length; $i++) {
			$rand = mt_rand(0, $max);
			$str .= $characters[$rand];
		}
		return $str;
	}
    

    public static function getTimePassed($first, $second)
    {
        $dateTimeObject1 = date_create($first);  
        $dateTimeObject2 = date_create($second);  
            
        // Calculating the difference between DateTime Objects 
        $interval = date_diff($dateTimeObject1, $dateTimeObject2);  
        //echo ("Difference in days is: "); 
        
        // Printing the result in days format 
        //echo $interval->format('%R%a days'); 
        //echo "\n<br/>"; 
        $min = $interval->days * 24 * 60; 
        $min += $interval->h * 60; 
        $min += $interval->i; 

        return $min; // I is minutes
    }

    public static function validateDate($date, $format = 'Y-m-d'){
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    } 

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION REFORMATS THE DATE
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */


    public static function reformatDate($input_date, $needed_format)
    {
        return date($needed_format, strtotime($input_date));
    }

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION GETS NEW DATE TIME AFTER GIVEN DATE TIME
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */


    public static function getDatePlusOrMinusDays($start_date, $change_days, $needed_format)
    {
        //$start_date = new DateTime();
        $start_date->modify($change_days);

        return $start_date->format($needed_format); //"Y-m-d"
        //date($needed_format, strtotime($input_date));

    }

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION SENDS A NOTIFICATION TO A USER
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
	public static function sendNotificationToUser($receiver_key, $priority, $title, $body){
        //echo "here";
        $lsp = LaundryServiceProvider::where('laundrysp_id', '=', 1)->first();
        if(empty($lsp->laundrysp_flagged_reason)){
            echo "fcm failed";
        }
        
        $credential = new ServiceAccountCredentials(
            "https://www.googleapis.com/auth/firebase.messaging",
            json_decode($lsp->laundrysp_flagged_reason, true)
        );

        $token = $credential->fetchAuthToken(HttpHandlerFactory::build());


        /////////////////////
        $apiurl = 'https://fcm.googleapis.com/v1/projects/memaww-d3160/messages:send';   //replace "your-project-id" with...your project ID

        $headers = [
                'Authorization: Bearer ' . $token["access_token"],
                'Content-Type: application/json'
        ];
       
        $notification_tray = [
                'title'             => $title,
                'body'              => $body,
            ];
       
        $in_app_module = [
                "title"          => "",
                "body"           => "",
            ];
        //The $in_app_module array above can be empty - I use this to send variables in to my app when it is opened, so the user sees a popup module with the message additional to the generic task tray notification.
       
         $message = [
               'message' => [
                    'token' => $receiver_key,
                    'notification'     => $notification_tray,
                    'data'             => $in_app_module,
                ],
         ];
       
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $apiurl);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
       
         $result = curl_exec($ch);
         curl_close($ch);
       
         //var_dump($result);
         if ($result === FALSE) {
            return false;
             //Failed
             //die('Curl failed: ' . curl_error($ch));
         } else {
            return true;
         }
       


        /*
        if(empty($receiver_key)){
            return false;
        }
        $headers = array(
            'Authorization: Bearer ' . $token["access_token"], 
            'Content-Type:application/json'
        );
        $fields = array(
            'token' => "fylpc2NwTSaB7nc84-84Ai:APA91bGv_76T6MdmsEFBoaOiPh8tP1gVkKiMZCCi31En7Fua1nF3_WZNKf2qIACUBAD0IK9HTlOvf9lo1X3J6ZysUFblkqGaUJjnrHfO-U8-FtamlM6fnaU",
            'priority' => $priority,
            'notification' => array(
                'title' => $title,
                'body' => $body,
                //'icon' => $message
            )
            );

            //var_dump($fields);
            $payload = json_encode($fields);
            $curl_session = \curl_init();
            curl_setopt($curl_session, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/memaww-d3160/messages:send");
            curl_setopt($curl_session, CURLOPT_POST, true);
            curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
            $curl_result = curl_exec($curl_session);
			
			
            
            echo "\n\n\n";
			echo $receiver_key;
			echo "\n\n\n";
			var_dump($curl_result);
            
            
			
			return true;
            */


	} 

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION SENDS A NOTIFICATION TO A TOPIC
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
    public static function sendNotificationToTopic($topic, $priority, $title, $body){

        if(!empty($topic)){
			$headers = array('Authorization:key=' . config("app.fcm_server_key"), 'Content-Type:application/json');
            $fields = array(
                'to' => '/topics/'. $topic,
                'priority' => $priority,
                'notification' => array(
                  'title' => $title,
                  'body' => $body,
                  //'icon' => $message
                )
                );

			$payload = json_encode($fields);
			$curl_session = \curl_init();
			curl_setopt($curl_session, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
			curl_setopt($curl_session, CURLOPT_POST, true);
			curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
			$curl_result = curl_exec($curl_session);

			/*
            echo "\n\n\n";
			var_dump($headers);
            echo "\n\n\n";
			var_dump($curl_result);
            */
            

			return true;
		} else {
			return false;
		}


	} 

    public static function addNotificationToUserQueue($title, $body, $topic_or_receiver_phone, $admin_pin){

        $notification["notification_title"] = $title;
        $notification["notification_body"] = $body;
        $notification["notification_topic_or_receiver_phone"] = $topic_or_receiver_phone;
        $notification["notification_sender_admin_id"] = $admin_pin;
        $notification = Notification::create($notification);

    }

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION GIVES A DISCOUNT
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
	public static function giveDiscount($discount_percentage, $discount_restricted_to_user_id, $discount_admin_name, $discount_reusable, $discount_can_be_used, $discount_expiry_date) 
    {
        $given_discount["discount_code"] = UtilController::getRandomString(8);
        $given_discount["discount_percentage"] = $discount_percentage;
        $given_discount["discount_restricted_to_user_id"] = $discount_restricted_to_user_id;
        $given_discount["discount_admin_name"] = $discount_admin_name;
        $given_discount["discount_reusable"] = $discount_reusable;
        $given_discount["discount_can_be_used"] = $discount_can_be_used;
        $given_discount["discount_expiry_date"] = $discount_expiry_date;
        return Discount::create($given_discount);
	}

    public static function verifyPayStackTransaction($transaction_id){
        $curl = curl_init();
        $key = config('app.payment_gateway_merchant_id');
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://prod.theteller.net/v1.1/users/transactions/".$transaction_id."/status",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Merchant-Id: $key"
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
          //echo "cURL Error #:" . $err;
          return json_decode('{"code":"FFF","status":"error"}');
        } else {
            $response_json = json_decode($response);
            //echo $response_json->status;
            //echo "$key \n\n " . $response;
            return $response_json;
        }    
    
    }

}