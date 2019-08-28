<?php

namespace KMM\Metrics;

class StatsD
{
    private $host;
    private $port;
    private $logger;

    public function tagKey($key)
    {
        //Adding key=value,key1=value1 after the metrics, sends it still via StatsD
        // but telegraf/influx is converting it to tags
        // https://www.influxdata.com/blog/getting-started-with-sending-statsd-metrics-to-telegraf-influxdb/
        if ($this->hostname) {
            $key = $key . ',krn_node_name=' . $this->hostname;
        }
        if ($this->sentryEnv) {
            $key = $key . ',krn_sentry_env=' . $this->sentryEnv;
        }

        return $key;
    }

    // Instantiate a new client
    public function __construct($host = 'localhost', $port = 8125, $hostname = null, $sentryEnv = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->hostname = $hostname;
        $this->sentryEnv = $sentryEnv;
    }

    // Record timing
    public function timing($key, $time, $rate = 1)
    {
        $key = $this->tagKey($key);
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
        $key = $this->tagKey($key);
        $begin = microtime(true);
        $callback();
        $time = floor((microtime(true) - $begin) * 1000);
        // And record
        $this->timing($key, $time, $rate);
    }

    // Record counting
    public function counting($key, $amount = 1, $rate = 1)
    {
        $key = $this->tagKey($key);
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
