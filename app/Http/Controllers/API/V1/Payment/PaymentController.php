<?php

namespace App\Http\Controllers\API\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\PaymentTrait;
use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\subscriptions;

class PaymentController extends Controller
{
    use PaymentTrait;
    public function handleAllPayments($id, Request $request)
    {
        $subscription = subscriptions::findOrFail($id);
        $paymentStatus = PaymentStatus::where('subscription_id', $id)->first();

        if (!$paymentStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Payment status not found',
                'data' => null
            ]);
        }

        $currentStage = $paymentStatus->stage; // Assuming 'stage' field indicates the current payment stage
        
        switch ($currentStage) {
            case 1:
                $amount = 1500.00; // Amount for first payment
                $message = 'رسم الاكتتاب';
                $statusUpdateFields = ['subscription_fee' => 1];
                break;

            case 2:
                $subscription = subscriptions::where('id', $id)->with('earth')->first();
                
                $space = $subscription->earth->space;
                $price = $subscription->earth->price;
                $trem = ($price * $space * 2.5) / 100;
                $amount = $trem;
             
                $message = 'رسم التأمين';
                $statusUpdateFields = ['insurance_fee' => 1];
               
                break;

            case 3:
                $subscription = subscriptions::where('id', $id)->with('earth')->first();
                if($subscription->state_data!=1){
                    return  response()->json([
                        'success' => false,
                        'message' => 'لم يتم تخصيصك بالمقسم بعد !',
                        'data' => null
                    ]);
                }
                $wieght = $subscription->earth->wieght;
                $price = $subscription->earth->price;
                $space = $subscription->earth->space;
                $trem = ($price * $space * 2.5) / 100;

               $amount = (($wieght/100 * $price)+ $price) * $space/4 - $trem;
                $message = 'رسم تخصيص المقسم';
                $statusUpdateFields = ['thirdBatch' => 1];
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment stage',
                    'data' => null
                ]);
        }
        //شيكت في حال كان المبلغ فارغ
        if($amount<=0){
            return response()->json([
                'success' => false,
                'message' => 'The amount was not specified',
                'data' => null
            ]);
        }
        $paymentData = $this->createPayment($id, $amount);
        // return $paymentData;
        // die();
        if ($paymentData['payment']->status == 1) { // Assuming status 1 means paid
            $paymentStatus->update($statusUpdateFields);
            $paymentStatus->stage =$paymentStatus->stage +1; // Move to the next stage
            $paymentStatus->save();
        }
        // if ($orderRef->is_success == 1) {
        //     $paymentStatus->stage = $paymentStatus->stage +1; // Move to the next stage
        //     $paymentStatus->save();
        // }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paymentData
        ]);
    }

//............................................................................//
    ///// update payment status
    public function paymentUpdate(Request $request)
    {
        $orderRef = Payment::where('orderKey', $request->OrderRef)->first();
       
        if (!$orderRef) {
            return response()->json([
                'success' => false,
                'message' => 'Payment or Payment Status not found',
                'data' => null
            ]);
        }
        
        $paymentStatus = PaymentStatus::where('subscription_id', $orderRef->subscription_id)->first();
        
        switch ($paymentStatus->stage) {
            case 1:
                $statusUpdateFields = ['subscription_fee' => 1];
                break;

            case 2:
                if ($paymentStatus->subscription_fee == 1) {
                    $statusUpdateFields = ['insurance_fee' => 1];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'First payment not completed',
                        'data' => null
                    ]);
                }
                break;

            case 3:
                if ($paymentStatus->subscription_fee == 1 && $paymentStatus->insurance_fee == 1) {
                    $statusUpdateFields = ['thirdBatch' => 1];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Previous payments not completed',
                        'data' => null
                    ]);
                }
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment stage',
                    'data' => null
                ]);
        }

        $paymentData = $this->handlePaymentUpdate($request, $statusUpdateFields);
        $orderRef = Payment::where('orderKey', $request->OrderRef)->first();

        if ($orderRef->status == 1) {
            $paymentStatus->stage = $paymentStatus->stage +1; // Move to the next stage
            $paymentStatus->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => $paymentData
        ]);
    }
 


   


  



}
