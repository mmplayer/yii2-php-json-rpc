<?php
namespace trifs\jsonrpc;

use Yii;
use trifs\jsonrpc\Server\Request\RequestInterface;
use trifs\jsonrpc\Server\Request\Batch;
use trifs\jsonrpc\Server\Request\Notification;
use trifs\jsonrpc\Server\Request\Request;
use trifs\jsonrpc\JSONRPCError;
use yii\base\UnknownClassException;

class Server
{
    const ERROR_PARSE_CODE       = -32700;
    const ERROR_INVALID_REQUEST  = -32600;
    const ERROR_METHOD_NOT_FOUND = -32601;
    const ERROR_INVALID_PARAMS   = -32602;
    const ERROR_INTERNAL_ERROR   = -32603;

    const MESSAGE_ERROR_PARSE_CODE       = 'Parse error';
    const MESSAGE_ERROR_INVALID_REQUEST  = 'Invalid Request';
    const MESSAGE_ERROR_METHOD_NOT_FOUND = 'Method not found';
    const MESSAGE_ERROR_INVALID_PARAMS   = 'Invalid params';
    const MESSAGE_ERROR_INTERNAL_ERROR   = 'Internal error';

    /**
     * Holds JSON input.
     *
     * @var string
     */
    private $input;

    /**
     * Holds callback invoker.
     *
     * @var callable
     */
    private $invoker;

    /**
     * Constructor, sets input and invoker.
     *
     * @param  string   $input
     * @param  callable $invoker
     * @throws \InvalidArgumentException if $input is not a string
     */
    public function __construct($input, $invoker)
    {
        // should assert() be used instead?
        if (false === is_string($input)) {
            throw new \InvalidArgumentException('$input has to be a string.');
        }

        $this->input   = (string)$input;
        $this->invoker = $invoker;
    }

    /**
     * Run and invoke the request.
     *
     * @return string
     */
    public function run()
    {
        $json = json_decode($this->input, true);

        if (empty($json)) {
            $result = $this->getErrorResponse(null, self::ERROR_PARSE_CODE, self::MESSAGE_ERROR_PARSE_CODE);
        } else {
            $request = $this->getRequest($json);

            if ($request->isBatch()) {
                $result = [];
                foreach ($request->getRequests() as $request) {
                    $result[] = $this->invoke($request);
                }
                // remove notifications
                $result = array_values(array_filter($result));
            } else {
                $result = $this->invoke($request);
            }
        }

        if (empty($result)) {
            return null;
        }

        return json_encode($result);
    }

    /**
     * Returns a RequestInterface object.
     *
     * @param  array $json
     * @return RequestInterface
     */
    private function getRequest(array $json)
    {
        // more than one
        if (isset($json[0])) {
            $request = new Batch($json);
        } elseif (false === isset($json['id'])) {
            $request = new Notification($json);
        } else {
            $request = new Request($json);
        }
        return $request;
    }

    /**
     * Returns an error response array.
     *
     * @param  mixed   $id
     * @param  integer $code
     * @param  string  $message
     * @return array
     */
    private function getErrorResponse($id, $code, $message)
    {
        return [
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     *
     * @param $e \Exception
     * @return exception message
     */
    private function formatErrorMessage($e){
        return "Exception at " . $e->getFile() . "(" . $e->getLine() . ":" . $e->getCode() . ") " . $e->getMessage() ;
    }
    /**
     * Invokes the request.
     *
     * @param  RequestInterface $request
     * @return array
     */
    private function invoke(RequestInterface $request)
    {
        if (defined('YII_DEBUG') && YII_DEBUG == true){
            try {
                $request->validate();

                $result = [
                    'jsonrpc' => '2.0',
                    'id'      => $request->getId(),
                    'result'  => call_user_func(
                        $this->invoker,
                        $request->getMethod(),
                        $request->getParameters()
                    ),
                ];
            } catch (\ReflectionException $e) { // on class not found
                Yii::error($this->formatErrorMessage($e), __METHOD__);
                $result = $this->getErrorResponse(
                    $request->getId(),
                    self::ERROR_METHOD_NOT_FOUND,
                    self::MESSAGE_ERROR_METHOD_NOT_FOUND
                );
            } catch (UnknownClassException $e){
                Yii::error($this->formatErrorMessage($e), __METHOD__);
                $result = $this->getErrorResponse(
                    $request->getId(),
                    self::ERROR_METHOD_NOT_FOUND,
                    self::MESSAGE_ERROR_METHOD_NOT_FOUND
                );
            } catch (JSONRPCError $e) { // A "NORMAL" error message display to user
                $result = $this->getErrorResponse(
                    $request->getId(),
                    $e->getCode(),
                    $e->getMessage()
                );
            } // Don't catch regular PHP regular \Exception in development mode
        } else {
            try {
                $request->validate();

                $result = [
                    'jsonrpc' => '2.0',
                    'id'      => $request->getId(),
                    'result'  => call_user_func(
                        $this->invoker,
                        $request->getMethod(),
                        $request->getParameters()
                    ),
                ];
            } catch (\ReflectionException $e) { // on class not found
                Yii::error($this->formatErrorMessage($e), __METHOD__);
                $result = $this->getErrorResponse(
                    $request->getId(),
                    self::ERROR_METHOD_NOT_FOUND,
                    self::MESSAGE_ERROR_METHOD_NOT_FOUND
                );
            } catch (UnknownClassException $e){
                Yii::error($this->formatErrorMessage($e), __METHOD__);
                $result = $this->getErrorResponse(
                    $request->getId(),
                    self::ERROR_METHOD_NOT_FOUND,
                    self::MESSAGE_ERROR_METHOD_NOT_FOUND
                );
            } catch (JSONRPCError $e) { // A "NORMAL" error message display to user
                $result = $this->getErrorResponse(
                    $request->getId(),
                    $e->getCode(),
                    $e->getMessage()
                );
            } catch (\Exception $e) { // unexpected exception occured at non-dev mode
                Yii::error($this->formatErrorMessage($e), __METHOD__);
                $result = $this->getErrorResponse(
                    $request->getId(),
                    $e->getCode(),
                    $e->getMessage()
                );
            }
        }

        if ($request->isNotification() && empty($result['error'])) {
            $result = null;
        }

        return $result;
    }
}
