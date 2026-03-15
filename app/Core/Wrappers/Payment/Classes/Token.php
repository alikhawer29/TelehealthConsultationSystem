<?php 
namespace App\Core\Wrappers\Payment\Classes;

class Token {

    private $config = [];
    private $card  = [];
    private $tokenData = [];
    
    public function __construct($config = [])
    {
        $this->config = $config;
    }
    
    public function generate($data = []){
        
        $this->card = $data;
    }

    public function setConfig($config = [])
    {
         $this->config = [...$this->config,...$config];
    }

}
?>