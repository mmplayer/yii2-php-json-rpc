<?php
namespace JsonRpc\Handler;

class Caculator {
    public function add($a, $b){
	return $a + $b;
    }
    public function subtract($a, $b){
	return $a - $b;
    }
}
