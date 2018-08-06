<?php

namespace KMM\Metrics;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

class Core
{
    private $plugin_dir;

    public function __construct($i18n)
    {
        global $wpdb;
        $this->requestStartMicro = null;
        $this->i18n = $i18n;
        $this->wpdb = $wpdb;
        $this->plugin_dir = plugin_dir_url(__FILE__) . '../';

        if (! defined('KRN_METRICS_STATSD_HOST')) {
            define('KRN_METRICS_STATSD_HOST', 'localhost');
        }
        if (! defined('KRN_HOST_SYSLOG')) {
            define('KRN_HOST_SYSLOG', 'localhost');
        }
        if (! defined('KRN_MY_HOSTNAME')) {
            define('KRN_MY_HOSTNAME', gethostname());
        }

        $this->statsd = new StatsD(KRN_METRICS_STATSD_HOST);

        $this->add_filters();
        $this->add_actions();
        $this->add_metabox();
    }

    public function add_metabox()
    {
    }

    public function add_filters()
    {
    }

    public function add_actions()
    {
        add_action('krn_send_stat', [$this, 'send_stat'], 10, 3);
        //CloudWatch
        //Needed for BC
        add_action('krn_log_cloudwatch', [$this, 'log_syslog'], 10, 2);
        add_action('krn_log_syslog', [$this, 'log_syslog'], 10, 2);

        add_action('save_post', [$this, 'save_post_start'], -1, 3);
        add_action('save_post', [$this, 'save_post_end'], 99999, 3);
        add_action('init', [$this, 'krn_init'], 10, 1);
        add_action('shutdown', [$this, 'krn_shutdown'], 10, 1);
    }

    public function krn_init()
    {
        $this->requestStartMicro = microtime(true);
    }

    public function krn_shutdown()
    {
        if ($this->requestStartMicro) {
            $timeConsumed = round(microtime(true) - $this->requestStartMicro, 3) * 1000;
            $uri = '';
            if (isset($_SERVER['REQUEST_URI'])) {
                $uri = $_SERVER['REQUEST_URI'];
                do_action('krn_send_stat', 'krn.backend.response.kind.http', 1, 'counting');
            } else {
                do_action('krn_send_stat', 'krn.backend.response.kind.nohttp', 1, 'counting');
            }
            $response_code = http_response_code();
            if ($response_code) {
                do_action('krn_send_stat', 'krn.backend.response.' . $response_code, 1, 'counting');
            }

            $family = 'other';
            if (preg_match("/wp\-admin/", $uri)) {
                $family = 'admin';
            }
            if (preg_match("/wp\-json/", $uri)) {
                $family = 'api';
            }
            if ($timeConsumed > 5000) {
                $reqUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI';
                do_action('krn_log_cloudwatch', 'Long Running Request (' . $timeConsumed . 'ms): ' . $reqUrl);
            }
            do_action('krn_send_stat', 'krn.backend.response.family.' . $family, 1, 'counting');
            do_action('krn_send_stat', 'krn.backend.response.time', $timeConsumed, 'timing');
            do_action('krn_send_stat', 'krn.backend.response.time.' . $family, $timeConsumed, 'timing');
        }
    }

    //Actual Methods
    public function send_stat($key, $value, $type)
    {
        if ($type == 'counting') {
            $this->statsd->counting($key, $value);
        }
        if ($type == 'timing') {
            $this->statsd->timing($key, $value);
        }
    }

    public function log_syslog($message, $level = 'warning')
    {
        $handler = new SyslogUdpHandler(KRN_HOST_SYSLOG, 514);
        $log = new Logger('krn.cloudwatch');

        $formatter = new LineFormatter('(BACKEND-' . KRN_MY_HOSTNAME . ") %level_name%: %message%\n");
        $handler->setFormatter($formatter);

        $log->pushHandler($handler);
        $log->$level($message);
    }

    public function save_post_start($post)
    {
        $this->save_post_start = microtime(true);
    }

    public function save_post_end($post)
    {
        $type = get_post_type($post);
        $this->save_post_end = microtime(true);
        $ms = round($this->save_post_end - $this->save_post_start, 3) * 1000;
        do_action('krn_send_stat', 'krn.backend.save_time.all', $ms, 'timing');
        do_action('krn_send_stat', 'krn.backend.save_time.' . $type, $ms, 'timing');
    }
}
