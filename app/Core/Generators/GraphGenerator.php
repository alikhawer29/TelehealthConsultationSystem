<?php

namespace App\Core\Generators;

use App\Core\Generators\Graph\Appointment;
use App\Core\Generators\Graph\Buyer;
use App\Core\Generators\Graph\Order;
use App\Core\Generators\Graph\Payment;
use App\Core\Generators\Graph\User;

class GraphGenerator
{

    protected $graphs = [
        'order' => Order::class,
        'payment' => Payment::class,
        'user' => User::class,
        'buyer' => Buyer::class,
        'appointment' => Appointment::class,
    ];
    protected $generator;

    public function __construct($type)
    {
        try {
            $this->generator = resolve($this->graphs[$type]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function data($data)
    {

        $this->generator->setData($data);
        return $this;
    }

    public function get()
    {
        return $this->generator->get();
    }
}
