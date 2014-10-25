<?php
namespace trifs\jsonrpc\Server\Request;

class Batch extends Request
{

    /**
     * Holds list of requests.
     *
     * @var array of Request
     */
    private $requests = array();

    /**
     * Handle batch request item callback
     *
     * @param  array $json
     * @return void
     */
    private function callback($json){
        $json = (array)$json;
        if (isset($json['id'])) {
            $this->requests[] = new Request($json);
        } else {
            $this->requests[] = new Notification($json);
        }
    }

    /**
     * Constructor, sets JSON request.
     *
     * Method is called through call_user_function
     * to make it work on PHP5.3
     *
     * @param  array $json
     * @return void
     */
    public function __construct(array $json)
    {
        array_walk($json, array($this, 'callback'));
    }

    /**
     * Returns a list of all available requests.
     *
     * @return array of RequestInterface
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * Returns a boolean flag indicating whether a request is a batch or not.
     *
     * @return boolean
     */
    public function isBatch()
    {
        return true;
    }
}
