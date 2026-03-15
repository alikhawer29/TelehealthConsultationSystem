<?php

namespace App\Core\Wrappers\OTP\Contracts;

abstract class OTPContract{

	protected function prepare_message($text, $data = []) {
		return str_replace(array_keys($data), array_values($data), $text);
	}

	protected function getNotificationClass($definedClass){
		if(!$definedClass){
			return $this->defaultNotificationClasses[$this->type];
        }
        
        return $definedClass;
	}
}
?>