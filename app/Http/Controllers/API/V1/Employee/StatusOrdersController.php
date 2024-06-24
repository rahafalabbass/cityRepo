<?php

namespace App\Http\Controllers\API\V1\Employee;

use App\Http\Controllers\Controller;
use App\Models\images;
use App\Models\Note;
use App\Models\Payment;
use App\Models\subscriptions;
use App\Traits\GeneralTrait;
use App\Traits\UploadImageTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusOrdersController extends Controller
{
    use GeneralTrait;
    use UploadImageTrait;
    
     // Update status order check
    public function updateStateCheck(Request $request)
    {
        try {
            if (!Auth::user()->role == 'employee') {
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            
            }
        
            $changeState = subscriptions::findOrFail($request->id);
            $changeState->update([
                'state_checked'=> 1
            ]);
            $notes = Note::where('subscription_id', $request->id)->update([
                'description' => $request->input('description') ?? 'تمت مراجعة طلبك , الرجاء انتظار وصول الموافقة الأمنية',
            ]);
            $paymentStatuses = $changeState->paymentStatus;

            $data = [$changeState, $notes,$paymentStatuses];

            return $this->buildResponse($data, 'Success','تمت مراجعة الطلب', 200);
        } catch (ModelNotFoundException $e) {
            return $this->buildResponse($e, 'Error', 'there is not update ', 404);
        }
    }


//update status for approval
public function updateStateApproval(Request $request)
    {
        try {
            if (!Auth::user()->role == 'employee') {
                return $this->buildResponse(null ,'Warning','unauthorized',401);            
            }
        
            $changeState = subscriptions::findOrFail($request->id);
            if($changeState->state_checked==1){
                $changeState->update([
                    'state_approval' => 1
               ]);
            
                $notes = Note::where('subscription_id', $request->id)->update([
                    'description' => $request->input('description') ?? 'وصلت الموافقة الأمنية, يرجى منك رفع الأوراق المطلوبة من لاستكمال طلبك',
                ]);
                $data = [$changeState, $notes];

                return $this->buildResponse($data, 'Success','سيتم إعلام المكتتب بوصول الموافقة الأمنية', 200);
            }else{
                return $this->buildResponse(null, 'Error', 'لايمكن قبول الطلب', 403);
            }
        }catch (ModelNotFoundException $e) {
            return $this->buildResponse($e, 'Error', 'there is not update ', 404);
        }
    }
// Update status order cancelled
    public function stateCancelled(Request $request)
    {
       
        try {
            if (Auth::user()->role != 'employee') {
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            }
            $changeState = subscriptions::findOrFail($request->id);
            $changeState->update([
                'state_cancelled' => 1
            ]);
           
            $notes = Note::where('subscription_id', $request->id)->update([
               'description' => $request->input('description') ?? 'تم رفض الطلب',
            ]);
            $data =[
                $changeState, $notes
            ];
            return $this->buildResponse($data, 'success','تم رفض الطلب', 200);
        } catch (ModelNotFoundException $e) {
            return $this->buildResponse($e, 'Error', 'حدث خطأ في عملية الغاء  الطلب', 404);
        }
    }
    //After uploaing new images ,بعد تشييك الموظف عالصور وقبولها
    public function updateStateComplate(Request $request)
    {
        try {
            if (!Auth::user()->role == 'employee') {
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            }
            //$changeState = subscriptions::findOrFail($request->id)-with('earth');
            $changeState = subscriptions::with('earth')->findOrFail($request->id);
            if($changeState->earth->weighting ==0){
                return $this->buildResponse(null ,'Warning','يجب اضافة معامل التثقيل للمقسم '. $changeState->earth->number,201);
            }
            if ($changeState->state_checked==1 && $changeState->state_approval == 1) {
                $changeState->update([
                    'state_complated' => 1
                ]);
               
                $notes = Note::where('subscription_id', $request->id)->update([
                    'description' => $request->input('description') ?? 'تمت مراجعة المرفقات , بانتظار قرار لجنة التخصيص',
                ]);
                $data =[
                    $changeState, $notes
                ];
                return $this->buildResponse($data, 'Success', 'تم.. سيتم إعلام المكتتب بأن ينتظر قرار لجنة التخصص', 200);
            }else{
                return $this->buildResponse(null, 'Error', 'لايمكن قبول الطلب', 403);
            }
        } catch (ModelNotFoundException $e) {
            return $this->buildResponse($e, 'Error', 'حدث خطأ', 404);
        }
    }

    
    //بعد وصول قرار التخصيص
    public function afterCustomization(Request $request)
    {
        try {
            if (!Auth::user()->role == 'employee') {
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            }
            $changeState = subscriptions::findOrFail($request->id);
            if ($changeState->state_checked==1 && $changeState->state_approval==1 && $changeState->state_complated==1) {
                $changeState->update([
                    'state_data' => 1
                ]);
             
                $notes = Note::where('subscription_id', $request->id)->update([
                    'description' => $request->input('description') ?? 'تم تخصيصك بالمقسم المطلوب يرجى تسديد رسم التخصيص',
                ]);
                
                $data =[$changeState, $notes];

                return $this->buildResponse($data, 'Success', 'تم تخصيص المكتتب بالمقسم المطلوب', 200);
            } 
            else {
                return $this->buildResponse(null, 'Error', 'لايمكن قبول الطلب', 403);            }
        } catch (ModelNotFoundException $e) {
            return $this->buildResponse($e, 'Error', 'there is not update ', 404);
        }
    }

    // For send messages to subscriber
    public function message(Request $request,$id){

        try {
            $message_id = Note::findOrFail($id);
            if(! $message_id){
                $message = Note::create([
                    'subscription_id'=> $request->id,
                     'description'=> $request->input('description') 
                ]);
            }
            $message= Note::where('subscription_id', $request->id)->update([
                'description' => $request->input('description')  ,
            ]);

           
            return $this->buildResponse($message, 'Success', 'تم ارسال الرسالة بنجاح', 200);
            
        }
        catch (ModelNotFoundException $e) {
            return $this->buildResponse($e, 'Error', 'حدث خطأ في ارسال الرسالة  ', 404);
        }
    }

    // delete image by employee
    public function delete($id){
        try{
         if (Auth::user()->role != 'employee') {
            return $this->buildResponse(null ,'Warning','unauthorized',401);
        }
         $image =images::where('id',$id)->first();
         if ($image) {
             $image->delete();
             return $this->buildResponse([], 'Success', 'تم حذف الصورة بنجاح', 200);
         } else {
             return $this->buildResponse(null, 'Error', 'هذه الصورة غير موجودة أو غير مرتبطة بك', 404);
         }
        } 
        catch (ModelNotFoundException $e) {
         return $this->buildResponse($e, 'Error', 'هناك خطأ في البحث عن الصورة', 404);
     }}

     
}

