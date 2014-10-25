<?php
use trifs\jsonrpc;
import("trifs/jsonrpc/Server",VENDOR_PATH,".php");
import("Action/JsonRpc/Executor",LIB_PATH);
class IndexAction extends Action {
  public function index(){
    $input = file_get_contents('php://input');
    $invoker=function($method, array $params = array()){
      $methodGroup=explode(".",$method);
      if (count($methodGroup) > 1) {
        $methodName=array_pop($methodGroup);
        $methodGroup=array_map(function ($word){
          return preg_replace('/_([a-z])/e',"strtoupper('\\1')",ucfirst($word));
        }, $methodGroup);
        $klassPath=implode("/",$methodGroup);
        import("Action/JsonRpc/Handler/".$klassPath,LIB_PATH);
        return \JsonRpc\Executor::invoke("\\JsonRpc\\Handler\\".str_replace("/","\\",$klassPath),$methodName,$params);
      } else {
        import("Action/JsonRpc/Handler/DefaultHandler",LIB_PATH);
        return \JsonRpc\Executor::invoke("\\JsonRpc\\Handler\\DefaultHandler",$method,$params);
      }
    };
    $server=new jsonrpc\Server($input, $invoker);
    $rpc_result=$server->run();
    if(!is_null($rpc_result)){
      $this->show($rpc_result,'utf-8','application/json');
    }
  }
}
