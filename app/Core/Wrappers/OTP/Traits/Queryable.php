<?php 

namespace App\Core\Wrappers\OTP\Traits;

use App\Core\Wrappers\OTP\Models\UserOtp;

trait Queryable {

	protected $otpModel = UserOtp::class;
	
	protected function 	storeOTP($to = null){
		// remove old otps to expire previous sent otps
        resolve($this->otpModel)->where('medium',$this->type)
        ->where('sent_on',$this->to)
        ->delete();
        // store new sent otp
        resolve($this->otpModel)->create([
            'code' => $this->code,
            'receivable_id' => $this->user->id??null, 
            'receivable_type' => $this->user?get_class($this->user):null,                                   
            'medium' => $this->type,
            'sent_on' => $this->to,
        ]);
	}


	protected function getOTP(){
		return resolve($this->otpModel)
			->where('medium',$this->type)
			->where('code',$this->code)
			
			->when($this->user,function($q){
				$q->where('receivable_id',$this->user->id)
				->where('receivable_type',get_class($this->user));
			})
			
			->when(!$this->user,function($q){
				$q->where('sent_on',$this->to);
			})->first();
	}
}
?>