<?php

namespace App\Core\Wrappers\OTP\Traits;

use App\Core\Wrappers\OTP\Models\UserOtp;

trait Verifiable{

	public function verify(){
		$otp = $this->getOTP();

		if($otp){
			return true;
		}else{
			return false;
		}
	}


}
?>