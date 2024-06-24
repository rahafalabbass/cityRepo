<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'orderKey',
        'amount',
        'status',
        'currency',  
        'language',
        'amountRef',
        'checkoutType',
        'transactionNo',
        'orderRef',
        'message',
        'is_success',
        'token',
        'paidDate',
        'subscription_id',
        'user_id',
        'status_id',
    ]; 

    public function paymentStatus(){
        return $this->belongsTo(PaymentStatus::class,'status_id');
    }
    

    
}