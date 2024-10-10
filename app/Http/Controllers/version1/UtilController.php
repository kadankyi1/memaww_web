<?php

namespace App\Http\Controllers\version1;

use DateTime;
use App\Http\Controllers\Controller;
use App\Models\version1\Discount;
use App\Models\version1\Notification;
use Illuminate\Http\Request;

class UtilController extends Controller
{
    // GENERATE LOGIN CODE
    public static function generate_passcode()
    {
        return rand(10000,99999);
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
        if(empty($receiver_key)){
            return false;
        }
        $headers = array('Authorization:key=' . config("app.fcm_server_key"), 'Content-Type:application/json');
        $fields = array(
            'registration_ids' => array($receiver_key),
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
			echo $receiver_key;
			echo "\n\n\n";
			var_dump($curl_result);
            */
            
			
			return true;


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

    public static function verifyPayStackTransaction($transaction_referennce_id){
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/:$transaction_referennce_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer SECRET_KEY",
            "Cache-Control: no-cache",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

}