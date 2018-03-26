<?php

namespace Mooc;

class Mooc extends User
{
    protected $baseUrl = 'https://www.icourse163.org';

    /**
     * Mooc constructor.
     * @param $cookie
     */
    public function __construct($cookie){
        parent::__construct($cookie,self::getCSRF($cookie),$this->baseUrl);
    }

}