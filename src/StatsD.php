<?php

namespace KMM\Metrics;

class StatsD
{
    private $host;
    private $port;
    private $logger;

    // Instantiate a new client
    public function __construct($host = 'localhost', $port = 8125, $logger = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->logger = $logger;
    }

    // Record timing
    public function timing($key, $time, $rate = 1)
    {
        $this->send("$key:$time|ms", $rate);
    }

    // Record gauge
    public function gauge($key, $gauge)
    {
        $this->send("$key:$gauge|g");
    }

    // Time something
    public function time_this($key, $callback, $rate = 1)
    {
        $begin = microtime(true);
        $callback();
        $time = floor((microtime(true) - $begin) * 1000);
        // And record
        $this->timing($key, $time, $rate);
    }

    // Record counting
    public function counting($key, $amount = 1, $rate = 1)
    {
        $this->send("$key:$amount|c", $rate);
    }

    // Send
    private function send($value, $rate = null)
    {
        $fp = @fsockopen('udp://' . $this->host, $this->port, $errno, $errstr);
        // Will show warning if not opened, and return false
        if ($fp) {
            @fwrite($fp, $rate ? "$value|@$rate" : $value);
            @fclose($fp);
        }
    }
}
