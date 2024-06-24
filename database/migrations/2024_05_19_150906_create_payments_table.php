<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('orderKey')->unique;
            $table->float('amount');
            $table->boolean('status')->default(0);
            $table->string('currency');
            $table->string('language');
            $table->string('checkoutType');
            
            $table->date('paidDate')->nullable();
            $table->float('amountRef')->nullable();
            $table->bigInteger('transactionNo')->nullable();
            $table->string('orderRef')->nullable();
            $table->string('message')->nullable();
            $table->boolean('is_success')->nullable();
            $table->string('token')->nullable();
           
            $table->unsignedBigInteger('subscription_id');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('status_id');
            $table->foreign('status_id')->references('id')->on('payment_statuses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
