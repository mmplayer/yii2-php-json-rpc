<?php
namespace JsonRpc\Handler;

class Caculator extends \Action{
    public function add($a, $b){
	return $a + $b;
    }
    public function subtract($a, $b){
	return $a - $b;
    }
}
