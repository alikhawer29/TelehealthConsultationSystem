<?php
namespace App\Core\Wrappers\Payment\Abstracts;

use App\Core\Wrappers\Payment\Classes\Token;
use App\Core\Wrappers\Payment\Drivers\Stripe;

class GatewayAbstract
{
    protected array $config;
    protected $gateway;

    private $drivers = [
        'stripe' => Stripe::class,
    ];
    
    protected function validateGateway($name = null){
        try {
            $resolver = config("gateway.{$name}");
            if(!$resolver){
                throw new \Exception('unable to find gateway driver '.$name);
            }
            $this->setConfig($name,$resolver);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function setConfig(string $driverKey,array $values){
        try {
            
            $credentials = $values['credentials'];
            $driver = $this->drivers[$driverKey];
            
            $this->setDriver($driver,$credentials);
            // $this->setTokenInstance();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function setDriver($driver = null, array $credentials = []){
        try {
            $this->gateway = resolve($driver,['credentials' => $credentials]);
            
        } catch (\Throwable $th) {
            throw $th;
        }    
    }

    protected function setTokenInstance(){
        $this->token = new Token($this->gateway->tokenConfiguration);
    }

}