<?php

namespace App\Http\Controllers\version1;

use App\Http\Controllers\Controller;
use App\Models\version1\Plans;
use App\Models\version1\Transaction;
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
    

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION VERIFIES A PAYMENT ON PAYSTACK
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */

    /*
    public static function verifyPayStackPayment($reference)
    {
        $url = "https://api.paystack.co/transaction/verify/" . $reference;
        $authorization =  "Authorization: Bearer " . config('app.paystacksecretkey');

        $curl = curl_init();
    
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            $authorization,
            "Cache-Control: no-cache",
        ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
        $response = json_decode($response);
        
        if ($err) {
            return response([
                "status" => "error", 
                "message" => "Failed to make request"
            ]);
        } else {
            $transaction = Transaction::where('transaction_payment_ref_id', '=', $reference)->first();
            if($transaction == null || empty($transaction->transaction_referenced_item_id)){
                return response([
                    "status" => "error", 
                    "message" => "Failed to make request"
                ]);
            }
            
            //var_dump($response); exit;
            if(!empty($response->data->status) && $response->data->status == "success"){
                $transaction->transaction_payment_status = "verified_passed";
                $transaction->save();  
            }
 

        return  $response;
        //echo $response;
        }
    }
    */

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | THIS FUNCTION CREATES A PAYSTACK PAYMENT PLAN (NOT A SUBCRIPTION)
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
    /*
    public static function createPayStackPaymentPlan($plan_name, $plan_interval, $plan_benefits_description)
    {
        $url = "https://api.paystack.co/plan";
        $authorization =  "Authorization: Bearer " . config('app.paystacksecretkey');

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => array(
          "name" => "Monthly Retainer",
          "interval" => "monthly",
          "amount" => 500000
        ),
        CURLOPT_HTTPHEADER => array(
            $authorization,
            "Cache-Control: no-cache",
        ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $response = json_decode($response);
        
        if ($err) {
            return response([
                "status" => "error", 
                "message" => "Failed to make request"
            ]);
        } else {
            
        //$planData["plan_id"] =  ;
        $planData["plan_name"] = $plan_name;
        $planData["plan_interval"] = $plan_interval;
        $planData["plan_benefits_description"] = $plan_benefits_description;
        //$plan = Plans::create($planData);

        return  $response;
        }
    }
*/
}