<?php

class RpcServer
{
    const TYPE_REQUEST = 0;
    const TYPE_RESPONSE = 1;
    const TYPE_NOTIFICATION = 2;

    private $msg_id = 0;

    private $current_msg_id;

    private $request_callback = [];

    public function __construct()
    {
        $this->unpacker = new MessagePackUnpacker;

        register_shutdown_function([$this, 'shutdown']);
    }

    public function loop()
    {
        $stdin = fopen('php://stdin', 'r');
        while ($buffer = fread($stdin, 1024)) {
            $this->unpacker->feed($buffer);

            if ($this->unpacker->execute()) {
                $message = $this->unpacker->data();
                $this->unpacker->reset();

                $this->onMessage($message);
            }
        }
    }

    public function shutdown()
    {
        if (!$this->current_msg_id) {
            return;
        }

        $error = error_get_last();
        $error = sprintf('"%s at %s:%d"',
            $error['message'], $error['file'], $error['line']);

        $response = [self::TYPE_RESPONSE, $this->current_msg_id, $error, null];
        $this->write($response);
    }

    private function onMessage($message)
    {
        $type = current($message);
        switch ($type) {
        case self::TYPE_REQUEST:
            $this->onRequest($message);
            break;
        case self::TYPE_RESPONSE:
            $this->onResponse($message);
            break;
        case self::TYPE_NOTIFICATION:
            $this->onNotification($message);
            break;
        }
    }

    private function onRequest($message)
    {
        $this->log("recv request", $message);
        list($type, $msg_id, $method, $params) = $message;

        $this->current_msg_id = $msg_id;

        $result = null;
        $error = null;
        if (method_exists($this, $method)) {
            $result = $this->doRequest([$this, $method], $params);
        } else {
            $this->log($method . " not exists");
            $error = 'method not exists';
        }
        $response = [self::TYPE_RESPONSE, $msg_id, $error, $result];
        $this->write($response);

        $this->current_msg_id = null;
    }

    private function onResponse($message)
    {
        $this->log("recv response", $message);
        list($type, $msg_id, $error, $result) = $message;
        $this->doCallback($msg_id, $error, $result);
    }

    private function onNotification($message)
    {
        $this->log("recv notice", $message);
        list($type, $method, $params) = $message;
        if (method_exists($this, $method)) {
            $this->doRequest([$this, $method], $params);
        }
    }

    protected function doRequest($callback, $params)
    {
        return call_user_func_array($callback, $params);
    }

    protected function call($method, $params, $callback = null)
    {
        if ($callback) {
            $msg_id = $this->getMessageId();
            $message = [self::TYPE_REQUEST, $msg_id, $method, $params];
            $this->addCallback($msg_id, $callback);
        } else {
            $message = [self::TYPE_NOTIFICATION, $method, $params];
        }

        $this->write($message);
    }

    private function addCallback($msg_id, $callback)
    {
        $this->request_callback[$msg_id] = $addCallback;
    }

    private function doCallback($msg_id, $error, $result)
    {
        if (!array_key_exists($msg_id, $this->request_callback)) {
            return;
        }

        $callback = $this->request_callback[$msg_id];
        $callback($error, $result);
    }

    private function getMessageId()
    {
        return $this->msg_id++;
    }

    private function write($data)
    {
        $this->log("write", $data);
        fwrite(STDOUT, msgpack_pack($data));
    }

    protected function log($log, $context = [])
    {
        $log = $log . '#' . json_encode($context, JSON_PRETTY_PRINT) . PHP_EOL;
        fwrite(STDERR, $log);
    }
}
