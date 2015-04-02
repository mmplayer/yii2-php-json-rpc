<?php
namespace trifs\jsonrpc;

use Yii;
use trifs\jsonrpc\Server;
use trifs\jsonrpc\JSONRPCError;
use yii\base\Object;

class Executor extends Object {

    private static $TAG="JSONRPC";

    // under which namespace the handlers will execute
    private $_handlerNamespace;

    // extra data
    private $_bundle;

    // setter
    public function setHandlerNamespace($ns){
        $this->_handlerNamespace = $ns;
    }

    public function setBundle($bundle){
        return $this->_bundle = $bundle;
    }

    public function getBundle(){
        return $this->_bundle;
    }
    /**
     * @param $type string|array|callable $type the object type.
     * @param $params array $params the constructor parameters
     * @param $method string to call
     * @param $methodParams array params passed to method
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @throws
     */
    private function invoke($type, $params, $method, array $methodParams) {
        if(is_null($params)) {
            $params = [];
        }
        // create instance from configuration
        $component = Yii::createObject($type, $params);
        if (method_exists($component, $method) && is_callable([$component, $method])) {
            $reflectionMethod = new \ReflectionMethod($component, $method);
            if ($reflectionMethod->isPublic()) {
                $requiredParamsCount = $reflectionMethod->getNumberOfRequiredParameters();
                $methodParamsCount=count($methodParams);
                if ($methodParamsCount < $requiredParamsCount) {
                    throw new JSONRPCError(Server::MESSAGE_ERROR_INVALID_PARAMS,Server::ERROR_INVALID_PARAMS);
                } else {
                    return call_user_func_array([$component, $method], $methodParams);
                }
            } else {
                throw new JSONRPCError(Server::MESSAGE_ERROR_METHOD_NOT_FOUND,Server::ERROR_METHOD_NOT_FOUND);
            }
        } else {
            throw new JSONRPCError(Server::MESSAGE_ERROR_METHOD_NOT_FOUND,Server::ERROR_METHOD_NOT_FOUND);
        }
    }

    public function invoker($method, array $params=[]) {
        $methodGroup = explode(".", $method);
        if (count($methodGroup) > 1) {
            $methodName = array_pop($methodGroup);
            $klassPath = implode("\\",
                array_map(["yii\\helpers\\BaseInflector","camelize"],
                    $methodGroup)
            );
            $klass=$this->_handlerNamespace . "\\" . $klassPath;
            Yii::info(self::$TAG . "\n class => ". $klass . "\n method => " . $methodName . "\n params => " . implode(",", $params));
            return $this->invoke([
                "class" => $klass,
                "bundle" => $this->bundle
            ], null, $methodName, $params);
        } else {
            $klass=$this->_handlerNamespace . "\\". "DefaultHandler";
            Yii::info(self::$TAG . "\n class => ". $klass . "\n method => " . $method . "\n params => " . implode(",", $params));
            return $this->invoke([
                "class" => $klass,
                "bundle" => $this->bundle
            ], null, $method, $params);
        }
    }

    public function execute($input) {
        $server = new Server($input, [$this,"invoker"]);
        return $server->run();
    }

    public function error($input, $message="unknown error", $code=-1){
        $server = new Server($input, function ($method, $params) use ($code, $message) {
            throw new JSONRPCError("Failed to execute: \"". $method . "\" " . $message, $code);
        });
        return $server->run();
    }
}
