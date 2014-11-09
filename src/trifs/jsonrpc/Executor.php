<?
namespace trifs\jsonrpc;

use Yii;
use trifs\jsonrpc\Server;

class Executor {
    private static function invoke($type, $params, $method, $methodParams) {
        if(is_null($params)) {
            $params = [];
        }
        $component = Yii::createObject($type, $params);
        if (method_exists($component, $method) && is_callable([$component, $method])) {
            $reflectionMethod = new \ReflectionMethod($component, $method);
            $requiredParamsCount = $reflectionMethod->getNumberOfRequiredParameters();
            $methodParamsCount=count($methodParams);
            if ($methodParamsCount < $requiredParamsCount) {
                return call_user_func("\\trifs\\jsonrpc\\Message::invalidParams");
            } else {
                return call_user_func_array([$component, $method], $methodParams);
            }
        } else {
            return call_user_func("\\trifs\\jsonrpc\\Message::methodNotFound");
        }
    }

    public static $handlerNamespace;

    public static function invoker($method, array $params=[]) {
        $methodGroup = explode(".", $method);
        if (count($methodGroup) > 1) {
            $methodName = array_pop($methodGroup);
            $klassPath = implode("\\",
                array_map(["yii\\helpers\\BaseInflector","camelize"],
                    $methodGroup)
            );
            return self::invoke([
                "class" => self::$handlerNamespace . "\\handlers\\" . $klassPath
            ], null, $methodName, $params);
        } else {
            return self::invoke([
                "class" => self::$handlerNamespace . "\\handlers\\DefaultHandler"
            ], null, $method, $params);
        }
    }

    public static function execute($input) {
       $server = new Server($input, ["trifs\\jsonrpc\\Executor","invoker"]);
       return $server->run();
    }
}
