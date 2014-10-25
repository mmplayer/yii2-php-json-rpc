<?php
namespace JsonRpc\Handler;

class DefaultHandler {
    public function test($a,$b){
	return "test".$a.$b;
    }
}
