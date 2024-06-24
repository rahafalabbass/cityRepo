<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'subscription_fee',
        'insurance_fee',
        'thirdBatch', 
        'stage' 
        
    ];

    protected $hidden = [
        'id', 
        'created_at', 
        'updated_at', 
        'subscription_id'
    ];

    public function subscription()
    {
        return $this->belongsTo(subscriptions::class, 'subscription_id');
    }


}
