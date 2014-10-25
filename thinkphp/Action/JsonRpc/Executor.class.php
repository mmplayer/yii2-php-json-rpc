<?php
namespace JsonRpc;
import("Action/JsonRpc/Message",LIB_PATH);
class Executor {
  public static function invoke($klass,$method,$params){
    if(method_exists($klass,$method) && is_callable($klass,$method)){
      $reflectionMethod=new \ReflectionMethod($klass,$method);
      $requiredParamsCount=$reflectionMethod->getNumberOfRequiredParameters();
      $paramsCount=count($params);
      if($paramsCount < $requiredParamsCount){
        return call_user_func("\\JsonRpc\\Message::invalidParams");
      }else {
        $reflectionKlass=new \ReflectionClass($klass);
        $klassInstance = $reflectionKlass->newInstanceArgs();
        return call_user_func_array(array($klassInstance,$method),$params);
      }
    } else {
      return call_user_func("\\JsonRpc\\Message::methodNotFound");
    }
  }
}
