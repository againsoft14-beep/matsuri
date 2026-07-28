<?php
namespace vendor\isenselabs\callforprice;

class Currency extends callforpriceCurrencyAlias {
    public function __construct($registry) {
        parent::__construct($registry);
    }

   public function format($number, $currency, $value = '', $format = true) {
       $string = parent::format($number, $currency, $value = '', $format = true);

       if ($number == -1 || $number == 'callforprice') {
           $string = false;
       }
       
        return $string;
    }
}