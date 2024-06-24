<?php

namespace App\Traits;

use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\subscriptions;
use Illuminate\Support\Facades\Auth;

trait PaymentTrait
{
    public function createPayment($subscriptionId, $amount)
    {
        $user = Auth::user();
        $paymentStatus = PaymentStatus::where('subscription_id', $subscriptionId)->first();
    
        $subscription = subscriptions::findOrFail($subscriptionId);
        if(!$user->id == $subscription->user_id){
            // kossy
            return response()->json([
                'success' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        if (!$user->id == $subscription->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        //
        $existingPayment = Payment::where('subscription_id', $subscriptionId)
        ->where('status', 0) // assuming 0 means unpaid
        ->first();
        if ($existingPayment) {
            $payment = $existingPayment;
        }
        else {
        $terminalId = rand(10000000, 99999999);
        $payment = Payment::create([
            'orderKey' => $terminalId,   // orderKey should be a string
            'amount' => $amount, // Variable amount
            'status' => 0, // Initial status
            'currency' => 'SYP',
            'language' => 'AR',
            'paidDate' => null,
            'checkoutType' => 'card',
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'status_id' => $paymentStatus->id, // Will be updated after payment is processed
        ]);
    }
        $checkoutUrl = sprintf(
            "%sCheckout/%s/%s/%s/%s/%s/%s/%s/%s/%s/%s",
            env('ECASH_BASE_URL'),
            $payment->checkoutType,  
            env('TERMINAL_KEY'),
            env('MERCHANT_KEY'),
            strtoupper(
                md5(env('MERCHANT_KEY') . env('MERCHANT_SECRET') . $payment->amount . $payment->orderKey)
            ),
            $payment->currency,
            $payment->amount,
            $payment->language,
            $payment->orderKey,
            env('REDIRECT_URL'), // ترميز الرابط
            env('CALLBACK_URL') // ترميز الرابط
        );

        return [
            'payment' => $payment,
            'checkoutUrl' => $checkoutUrl
        ];
    }

    public function handlePaymentUpdate($request,array $statusUpdateFields)
    {
        $orderRef = Payment::where('status',0)->where('orderKey', $request->OrderRef)->first();
        if(!$orderRef){
            return response()->json([
                'data' => null,
                'type'=>'Error',
                // 'message'=>'The transaction number is incorrect',
                'message'=>'رقم المعاملة غير صحيح',
                'status'=>400,
            ]);
        }
        if($orderRef->amount != $request->Amount){
            return response()->json([
                'data' => null,
                'type'=>'Error',
                // 'message'=>'There is an error in amount',
                'message'=>'المبلغ المدخل غير صحيح',
                'status'=>400,
            ]);
        }
        $orderRef->update([
            'orderRef' => $request->OrderRef,
            'transactionNo' => $request->TransactionNo,   
            'amountRef' => $request->Amount,
            'token' => $request->Token,
            'message' => $request->Message,
            'paidDate' => now(),
            'is_success'=> $request->IsSuccess,
            'status' => 1,
        ]);
        $paymentStatus = PaymentStatus::where('subscription_id', $orderRef->subscription_id)->first();

        if ($orderRef->is_success == 1) {
            $paymentStatus->update($statusUpdateFields);
        }
        $orderRef->makeHidden(['created_at', 'updated_at']);
        $paymentStatus->makeHidden(['created_at', 'updated_at']);

        return [
            'payment' => $orderRef,
            'paymentStatus' => $paymentStatus
        ];
    }
    
}