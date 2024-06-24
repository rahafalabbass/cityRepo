<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Http\Resources\EarthResource;
use App\Models\Areas;
use App\Models\earths;
use App\Models\User;
use App\Traits\GeneralTrait;
use App\Http\Requests\areaRequest;
use App\Http\Requests\earthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AreaController extends Controller
{
    //use AreaResource;
    use GeneralTrait;
    // FUNCTUON FOR SHOW AREAS
    public function show_areas(){

        $areas = Areas::get();

        return AreaResource::Collection($areas);
    }

//----------------------FUNCTION FOR SHOW EARTH------------------------------------------
    public function show_earths($id){

        try{
            $earths = earths::where('area_id', $id)->where('available','0')->get();
            return EarthResource::Collection($earths);

        }catch(\Exception $ex) {
            return $this->buildResponse($ex, 'Error', 'غير موجود ', 404);
        }
    }
    
     //...................................show_earth with checking

     
    public function showEarths($id){

        try {
             $user = Auth::user();             
        
            $availableLands = earths::whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('area_id',$id )->get();
            

            if ($availableLands->isEmpty()) {
                // إذا لم يكن المستخدم حجز أي أرض، عرض جميع الأراضي المتاحة
                return $this->buildResponse($availableLands, null,null, 200);

            } else {
                // إذا كان المستخدم قد حجز أراضي مسبقًا، احصل على قائمة الأراضي التي يمكنه حجزها
                return $this->buildResponse($availableLands, 'Success', ' اختر المقسم', 200);
            }
        
        }
        catch(\Exception $ex) {
            return $this->buildResponse($ex,'Error',null , 500);
        }
    }

    //............... Add Area...................

    public function createArea(areaRequest $request){
        try{
            if (Auth::user()->role != 'employee') {
    
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            }
            $image = $request->file('url_image')->getClientOriginalName();
            $request->file('url_image')->storeAs('areas',$image,'public');
            
            $area = Areas::create([
                'name'=>$request->input('name'),
                'url_image'=> $image
            ]);
            return $this->buildResponse($area, 'Success', 'تمت إضافة المنطقة بنجاح',200);
        }catch(\Exception $ex) {
            return $this->buildResponse($ex,'Error','لم يتم إضافة المنطقة بشكل ناجح ' , 500);
        }

    }

      //.......................UPDATE AREA............................

      public function updateArea(Request $request, $id) {
        try {
            if (Auth::user()->role != 'employee') {
                return $this->buildResponse(null, 'Warning', 'unauthorized', 401);
            }
            $validated = $request->validate([
                'name' => 'string',
                "url_image" => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
    
            $area = Areas::findOrFail($id);
            if(!empty($request->name)){
              $area->update([
                'name'=>$request->input('name'),
              ]);
            }
    
            if ($request->hasFile('url_image')) {
                if ($area->url_image) {
                    Storage::disk('public')->delete('areas/' . $area->url_image);
                }
    
                $image = $request->file('url_image')->getClientOriginalName();
                $request->file('url_image')->storeAs('areas', $image, 'public');
                $area->url_image = $image;
            }
    
            $area->save();
    
    
            return $this->buildResponse($area, 'Success', 'تم تحديث المنطقة بنجاح', 200);
    
        } catch (\Exception $ex) {
            return $this->buildResponse($ex, 'Error', 'لم يتم تحديث المنطقة بشكل ناجح', 500);
        }
    }
    
    
    //...........Create Earth...............

    public function createEarth(earthRequest $request,$id){
        try{
            if (Auth::user()->role !== 'employee') {
    
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            }
            $newNumber = $request->input('number');
      
            if (earths::where('number', $newNumber)->exists()) {
                return $this->buildResponse(null, 'Error', 'رقم الأرض موجود بالفعل', 400);
            }
          
            $earth = earths::create([
                'number'=>$newNumber,
                'space'=>$request->input('space'),
                'electricity'=>$request->input('electricity'),
                'price'=>$request->input('price'),
                'available'=>0,
                'weighting'=>$request->input('weighting') ? $request->input('weighting')  : 0,
                'area_id'=>$id
            ]);
            return $this->buildResponse($earth, 'Success', 'تمت إضافة المقسم بنجاح',200);
        }catch(\Exception $ex) {
            return $this->buildResponse($ex,'Error','لم يتم إضافة المقسم بشكل ناجح ' , 500);
        }

    }

  
//................. UPDATE EARTH.................................
public function updateEarth(earthRequest $request, $id) {
    try {
        if (Auth::user()->role != 'employee') {
            return $this->buildResponse(null, 'Warning', 'unauthorized', 401);
        }
        $earth = earths::findOrFail($id);

        $newNumber = $request->input('number');
      
        if ($newNumber != $earth->number && earths::where('number', $newNumber)->exists()) {
            return $this->buildResponse(null, 'Error', 'رقم الأرض موجود بالفعل', 400);
        }
        // $term =$request->input('weighting');
        // $weighting=number_format($term, 5);
        $earth->update([
            'number' => $newNumber,
            'space' => $request->input('space'),
            'electricity' => $request->input('electricity'),
            'price' => $request->input('price'),
            'weighting'=>$request->input('weighting'),
            'area_id' => $earth->area_id,
        ]);

        return $this->buildResponse($earth, 'Success', 'تم حفظ بيانات المقسم الجديدة بنجاح', 200);

    } catch (\Exception $ex) {
        return $this->buildResponse($ex, 'Error', 'خطأ في حفظ التعديل', 500);
    }
}



    //.............

    public function areaWithEarths()
    { 
        try{
            
            if (!Auth::user()->role == 'employee') {
                return $this->buildResponse(null ,'Warning','unauthorized',401);
            }
            $areas = Areas::get();
            $earths = [];
            foreach($areas as $area){
                $area->details = earths::where('area_id', $area->id)->get();
                foreach($area->details as $detail) {
                    $detail->weighting = number_format($detail->weighting, 5);
                }
                array_push($earths, $area);
            }
        
            return $this->buildResponse($earths, 'Success', 'جميع مناطق المدينة',200);

        }catch (\Exception $ex){
            return $this->buildResponse([],'[Error',$ex->getMessage(), 500);
        }
    }
        
        
}
