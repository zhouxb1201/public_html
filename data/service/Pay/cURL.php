<?php
namespace data\service\Pay;
/**
在webbrowser的PHP-CGI模式下，至少需要2个PHP独立端口来完成任务。一个的话，就是自己把自己阻塞死掉。
CURL HTTP: post/get

Todo:
1, cookie
2, session

Example 1: Force request the special ip, ignore local hosts file, dns server.
$target_ip = '1.111.211.110';
$url = 'http://p.klzxbf.com/so/acton.php?id=7923&pass=frame3232#fome232323';
$params = array('my' => '1st parameter', 'second' => '1');
$content = cURL::factory()->fake_ip('8.8.8.4')->force_ip($target_ip)->get($url, $params, 'http://www.baidu.com/?kw=chinaMobile');

Exmaple 2: Upload file
$target_ip = '127.0.0.1';
$url = 'http://localhost/upload.php';
$params = array('gg' => " My is GG value&sdf=ynf%20%%20\r\nNew Line By system.", 'save' => 'leap.php', 'att' => "@test_dirlist.php");
// multiple attachment
//$params = array('pictures[0]' => "@cat.jpg", 'pictures[1]' => "@dog.jpg");

$content = cURL::factory()->fake_ip('8.8.8.4')->force_ip($target_ip)->post($url, $params, 'http://www.baidu.com/?kw=chinaMobile');
print_r($content);

cookie file format:

Domain\tHttpOnly\tPath\tSecure\tExpire\tCookieName\tCookieValue\n


 * @author redblade
 *
 */

define('IS_DEBUG_CURL', FALSE);

class cURL {

    public static $instance = NULL;

    public static function factory($config = NULL)
    {
        if (! isset(self::$instance))
        {
            self::$instance = new cURL($config);
        }

        return self::$instance;
    }


    private $ch = NULL;

    private $timeout = 5;

    public $target_ip = NULL;

    public $response = '';

    public $response_header = '';

    public $response_body = '';

    public $response_http_code = 100;

    public $cookie_file = './curl_cookie.txt';

    private $use_cookie = FALSE;

    private $referer = NULL;

    private $options = array(
            CURLOPT_HEADER => TRUE,
            CURLOPT_VERBOSE => 0,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'cURL API Utility/1.0 (compatible;)',
            CURLOPT_DNS_CACHE_TIMEOUT => 1800
    );

    private $reqest_headers = array('Expect:');

    private $domain = NULL;

    private $append_cookie_data = array();

    public function __construct($config = NULL)
    {

    }

    /**
     * 强制把域名指向到该IP地址，忽略Host、DNS服务器等域名设置
     * 但是注意：
     * 本处做法只能访问那些直接能访问的服务器，
     * 不能经过中间层(F5、Nginx等)负载均衡过去。
     *
     * @param string $target_ip
     */
    public function force_ip($target_ip)
    {
        $this->target_ip = $target_ip;
        return $this;
    }

    public function fake_ip($client_ip)
    {
        $this->reqest_headers[] = 'X-FORWARDED-FOR:'.$client_ip;
        $this->reqest_headers[] = 'X-REAL-IP:'.$client_ip;
        $this->reqest_headers[] = 'CLIENT-IP:'.$client_ip;
        $this->reqest_headers[] = 'REMOTE-ADDR:'.$client_ip;
        return $this;
    }

    private function prepare($url, $data = array(), $referrer = NULL, $timeout = 5, $headers = array())
    {
        $this->headers($headers);
        $this->referrer($referrer);
        $this->set_timeout($timeout);
        $this->options[CURLOPT_REFERER] = $this->referer;
        $this->options[CURLOPT_HTTPHEADER] = $this->reqest_headers;
        $this->options[CURLOPT_CONNECTTIMEOUT] = $this->timeout;
        return $this;
    }

    public function get($url, $params = array(), $referrer = NULL, $timeout = 5, $headers = array())
    {
        $this->prepare($url, $params, $referrer, $timeout, $headers);
        $this->options[CURLOPT_URL] = $this->get_url($url, $params);
        echo $this->get_url($url, $params).'<br>';
        $this->options[CURLOPT_CONNECTTIMEOUT] = $this->timeout;
        $this->options[CURLOPT_HTTPGET] = TRUE;
        $ret = $this->execute(FALSE);
        return $ret === FALSE ? FALSE : $this->response();
    }

    public function post($url, $data = array(), $referrer = NULL, $timeout = 5, $headers = array())
    {
        $this->prepare($url, $data, $referrer, $timeout, $headers);
        $this->options[CURLOPT_URL] = $this->get_url($url);
        $this->options[CURLOPT_POST] = TRUE;
        $this->options[CURLOPT_POSTFIELDS] = $data;

        $ret = $this->execute(FALSE);
        return $ret === FALSE ? FALSE : $this->response();
    }

    public function response()
    {
        $response_info = curl_getinfo($this->handle());
        $this->destroy_curl();
        list ($this->response_header, $this->response_body) = explode("\r\n\r\n", $this->response, 2);
        if ($response_info['http_code'] != 200)
        {
            $msg = IS_DEBUG_CURL ? $response_info['http_code'].':'.$this->response_body : '';

            return $this->halt($msg, FALSE);
        }

        return array('header' => $this->response_header, 'body' => $this->response_body);
    }

    public function headers($headers = array())
    {
        if (empty($headers))
        {
            return $this->response_header;
        }
        else
        {
            $this->reqest_headers = array_merge($headers, $this->reqest_headers);
            return $this;
        }
    }

    public function cookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = FALSE, $httponly = FALSE)
    {
        $this->append_cookie_data[] = array(
                'name' => $name,
                'value' => $value,
                'expire' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly
        );
        $this->use_cookie = TRUE;
        return $this;
    }

    public function set_cookie_file($cookie_file = NULL)
    {
        if (! is_null($cookie_file))
        {
            $this->cookie_file = $cookie_file;
        }
        $this->use_cookie = TRUE;
        return $this;
    }

    public function cookies($data = array())
    {
        foreach ($data as $d)
        {
            $this->append_cookie_data[] = array(
                    'name' => $d['name'],
                    'value' => $d['value'],
                    'expire' => isset($d['expire']) ? $d['expire'] : 0,
                    'path' => isset($d['path']) ? $d['path'] : '/',
                    'domain' => isset($d['domain']) ? $d['domain'] : '',
                    'secure' => (isset($d['secure']) and $d['secure']) ? 'TRUE' : 'FALSE',
                    'httponly' => (isset($d['httponly']) and $d['httponly']) ? 'TRUE' : 'FALSE'
            );
        }
        $this->use_cookie = TRUE;
        return $this;
    }

    public function append_cookie($data = array())
    {
    }

    public function referrer($referrer = '')
    {
        $this->referer = $referrer;
        return $this;
    }

    public function set_timeout($timeout = 5)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function set_useragent($useragent = '')
    {
        if (! empty($useragent))
        {
            $this->options[CURLOPT_USERAGENT] = $useragent;
        }
        return $this;
    }

    private function handle()
    {
        if (! is_resource($this->ch))
        {
            $this->ch = curl_init();
        }

        return $this->ch;
    }

    private function encoder($data = array())
    {
        return empty($data) ? '' : http_build_query($data);
    }

    public function use_ssl()
    {
        $this->options[CURLOPT_SSL_VERIFYHOST] = 2;
        $this->options[CURLOPT_SSL_VERIFYPEER] = FALSE;
    }

    /**
     * 设置代理
     *
     * @param $proxy_ip
     * @param $proxy_port
     * @param $type 代理类型，仅仅支持 http和socks5
     * @param $user
     * @param $password
     */
    public function proxy($proxy_ip, $proxy_port, $type = 'http', $user = '', $password = '')
    {
        $this->options[CURLOPT_HTTPPROXYTUNNEL] = TRUE;
        $this->options[CURLOPT_PROXY] = $proxy_ip . ':' . $proxy_port;
        $this->options[CURLOPT_PROXYTYPE] = $type == 'http' ? CURLPROXY_HTTP : CURLPROXY_SOCKS5;
        if (! empty($user))
        {
            $this->options[CURLOPT_PROXYUSERPWD] = $user . ':' . $password;
        }
        return $this;
    }

    private function get_url($url, $params = array())
    {
        $urldata = parse_url($url);
        if (strtolower($urldata['scheme']) == 'https')
        {
            $this->use_ssl();
        }
        $flag_contact = $query_string = '';
        if (! empty($this->target_ip))
        {
            $urldata['port'] = isset($urldata['port']) ? $urldata['port'] : '80';
            // $this->options[CURLOPT_PROXY] = $this->target_ip.':'.$urldata['port'];
            $this->proxy($this->target_ip, $urldata['port']);
            if (IS_DEBUG_CURL)
            {
                echo $this->target_ip . ':' . $urldata['port'] . "\r\n";
            }
        }

        if (! empty($params))
        {
            $query_string = $this->encoder($params);
            $flag_contact = strpos($url, '?') === FALSE ? '?' : '&';
        }
        if (IS_DEBUG_CURL)
        {
            echo $url . $flag_contact . $query_string . "\r\n";
        }

        $this->domain = $urldata['host'];
        return $url . $flag_contact . $query_string;
    }

    public function get_exist_cookies()
    {
        $exist_cookie_data = array();
        if (! empty($this->append_cookie_data) and is_file($this->cookie_file))
        {
            $content = file_get_contents($this->cookie_file);
            if (! empty($content))
            {
                $cookie_lines = explode("\n", str_replace("\r\n", "\n", $content));
                foreach ($cookie_lines as $cookie_line)
                {
                    if (empty($cookie_line) or preg_match('/^#/', $cookie_line))
                        continue;

                    $cookie_fields = explode("\t", $cookie_line);
                    $exist_cookie_data[$cookie_fields[0] . '_' . $cookie_fields[5]] = array(
                            'name' => urldecode($cookie_fields[5]),
                            'value' => urldecode($cookie_fields[6]),
                            'expire' => $cookie_fields[4],
                            'path' => urldecode($cookie_fields[2]),
                            'domain' => $cookie_fields[0],
                            'secure' => $cookie_fields[3],
                            'httponly' => $cookie_fields[1]
                    );
                }
            }
        }
        return $exist_cookie_data;
    }

    private function execute($halt = FALSE)
    {
        $this->curl_cookie();
        curl_setopt_array($this->handle(), $this->options);
        $this->response = curl_exec($this->handle());
        $error_no = curl_errno($this->handle());
        if ($error_no)
        {
            $msg = curl_error($this->handle());
            $this->destroy_curl();
            $this->halt('Error occured: NO.' . $error_no . ', ' . $msg, $halt);

            return FALSE;
        }
    }

    private function curl_cookie()
    {
        // echo "HostName: ".$this->domain."\n";
        if (! $this->use_cookie)
            return FALSE;

        if (! is_file($this->cookie_file))
        {
            file_put_contents($this->cookie_file, '');
        }
        $data_cookies = array();
        foreach ($this->append_cookie_data as $append_cookie)
        {
            $domain = $this->get_domain($append_cookie['domain']);
            $data_cookies[$domain . '_' . $append_cookie['name']] = $append_cookie;
        }
        $data_cookies += $this->get_exist_cookies();

        $output_cookie = array();
        foreach ($data_cookies as $data_cookie)
        {
            $domain = $this->get_domain($data_cookie['domain']);
            $output_cookie[] = $domain . "\t" . $data_cookie['httponly'] . "\t" . $data_cookie['path'] . "\t" . $data_cookie['secure'] . "\t" . $data_cookie['expire'] . "\t" . urlencode($data_cookie['name']) . "\t" .
                     urlencode($data_cookie['value']);
        }
        if (! empty($output_cookie))
        {
            $content_pre = "# Netscape HTTP Cookie File\n" . "# http://curl.haxx.se/rfc/cookie_spec.html\n" . "# This file was generated by libcurl! Edit at your own risk.\n" . "\n";
            file_put_contents($this->cookie_file, $content_pre . implode("\n", $output_cookie) . "\n");
        }

        $this->options[CURLOPT_COOKIEJAR] = $this->cookie_file;
        $this->options[CURLOPT_COOKIEFILE] = $this->cookie_file;
    }

    private function get_domain($domain)
    {
        if (is_null($domain))
        {
            $domain = 'localhost';
        }
        elseif ($domain == '')
        {
            $domain = $this->domain;
        }
        return $domain;
    }

    private function destroy_curl()
    {
        if (is_resource($this->handle()))
        {
            curl_close($this->handle());
            $this->ch = NULL;
        }
    }

    private function get_httpcode()
    {
    }

    public function close()
    {
        $this->destroy_curl();
        if (is_file($this->cookie_file))
        {
            unlink($this->cookie_file);
        }
    }

    private function halt($msg = '', $is_exit = TRUE, $title = '')
    {
        if (! empty($title) AND IS_DEBUG_CURL)
        {
            echo "Title: $msg;;;;\r\n";
        }
        if (! empty($msg) AND IS_DEBUG_CURL)
        {
            echo "Message: $msg;;;;\r\n";
        }
        if ($is_exit)
        {
            exit();
        }
        else
        {
            return FALSE;
        }
    }
}