<?php
namespace JsonRpc;
use trifs\jsonrpc\Server;
import("trifs/jsonrpc/Server",VENDOR_PATH,".php");
class Message {
  public static function methodNotFound(){
    throw new \Exception(Server::MESSAGE_ERROR_METHOD_NOT_FOUND,Server::ERROR_METHOD_NOT_FOUND);
  }
  public static function invalidParams(){
    throw new \Exception(Server::MESSAGE_ERROR_INVALID_PARAMS,Server::ERROR_INVALID_PARAMS);
  }
}
