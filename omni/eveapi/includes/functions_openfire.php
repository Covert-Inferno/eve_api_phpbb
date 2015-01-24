<?php

class OpenFireUserService
{
    private $settings = array(
      'secret'    => 'secret',
      'host'			=> 'localhost',
      'port'			=> '9090',
      'plugin'		=> '/plugins/userService/',
      'useSSL'		=> false,
    );

    private $response;
    private $url;
    private $path;
    private $options = array();

    private function doRequest()
    {
        $base = ($this->useSSL) ? "https" : "http";
        $this->url = $base . "://" . $this->host . ':' . $this->port . $this->plugin . $this->path;
        $this->options['headers'] = array(
          'Authorization' => $this->secret,
        );
        $this->response = null;

        return $this->doRequestCurl();
    }

    private function doRequestCurl()
    {
        $this->response = new stdClass();

        $uri = @parse_url($this->url);

        if ($uri == false)
        {
          $this->response->code = -1;
          return false;
        }

        if (!isset($uri['scheme']))
        {
          $this->response->code = -1;
          return false;
        }

        $options = $this->options + array(
          'data' => '',
          'headers' => array(),
          'method' => 'POST',
          'timeout' => 45,
        );

        $curl = curl_version();

        $curl_opt = array(
          CURLOPT_HEADER => true,
          CURLINFO_HEADER_OUT => true,
          CURLOPT_TIMEOUT => $options['timeout'],
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_URL => $this->url,
          CURLOPT_PORT	=> $this->port,
          CURLOPT_NOBODY => false,
          CURLOPT_FORBID_REUSE => false,
          CURLOPT_CONNECTTIMEOUT => ceil($options['timeout'] / 2),
          CURLOPT_UNRESTRICTED_AUTH => true,
          CURLOPT_SSL_VERIFYPEER => true,
          CURLOPT_SSL_VERIFYHOST => 2,
        );

        $ssl_version = $curl['ssl_version'];
        $has_nss = (strpos($ssl_version, "NSS") > -1);

        if ($has_nss)
        {
          $curl_opt += array(CURLOPT_SSL_CIPHER_LIST => 'rsa_aes_128_sha,rsa_aes_256_sha,rsa_3des_sha,rsa_rc4_128_sha,rsa_rc4_128_md5');
        }
        else
        {
          $curl_opt += array(CURLOPT_SSL_CIPHER_LIST => 'AES128-SHA AES256-SHA DES-CBC3-SHA RC4-SHA RC4-MD5');
        }

        $options['headers']['Host'] = $uri['host'] . ($this->port != 80 ? ':' . $this->port : '');

        // Only add Content-Length if we actually have any content or if it is a POST
        // or PUT request. Some non-standard servers get confused by Content-Length in
        // at least HEAD/GET requests, and Squid always requires Content-Length in
        // POST/PUT requests.
        $content_length = strlen($options['data']);

        if ($content_length > 0 || $options['method'] == 'POST' || $options['method'] == 'PUT')
        {
          $options['headers']['Content-Length'] = $content_length;
        }

        // Set the request method.
        switch ($options['method'])
        {
          case 'GET':
            $curl_opt[CURLOPT_HTTPGET] = true;
            break;

          case 'POST':
            $curl_opt[CURLOPT_POST] = true;
            break;

          case 'PUT':
            $curl_opt[CURLOPT_CUSTOMREQUEST] = "PUT";
            break;

          case 'DELETE':
            $curl_opt[CURLOPT_CUSTOMREQUEST] = "DELETE";
            break;

          default:
            $this->response->error = 'invalid method ' . $options['method'];
            $this->response->code = -1;
            return false;
        }

        if (!empty($options['data']))
        {
          $curl_opt[CURLOPT_POSTFIELDS] = $options['data'];

          $options['headers'] += array(
            'Content-Type' => 'application/xml',
          );
        }

        // Set all the headers.
        $curl_opt[CURLOPT_HTTPHEADER] = array();

        foreach ($options['headers'] as $name => $value)
        {
          $curl_opt[CURLOPT_HTTPHEADER][] = $name . ": " . trim($value);
        }

        // Make the request.
        $ch = curl_init();
        curl_setopt_array($ch, $curl_opt);
        $this->response->data = curl_exec($ch);
        $this->response->error = curl_error($ch);
        $this->response->errno = curl_errno($ch);

        // If there's been an error, do not continue.
        if ($this->response->error)
        {
          // Request timed out.
          if (CURLE_OPERATION_TIMEOUTED == $this->response->errno)
          {
            $this->response->code = HTTP_REQUEST_TIMEOUT;
            $this->response->error = 'request timed out';
            return false;
          }

          $this->response->code = $this->response->errno;
          return false;
        }
      
        // The last effective URL should correspond to the Redirect URL.
        $this->response->redirect_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        // Save the request sent into the result object.
        $this->response->request = curl_getinfo($ch, CURLINFO_HEADER_OUT);

        // Parse response headers from the response body.
        // Be tolerant of malformed HTTP responses that separate header and body with
        // \n\n or \r\r instead of \r\n\r\n.
        list($response, $this->response->data) = preg_split("/\r\n\r\n|\n\n|\r\r/", $this->response->data, 2);

        // Sometimes when making an HTTP request via proxy using cURL, you end up with
        // a multiple set of headers:
        // from the web server being the actual target, from the proxy itself, etc.
        // The following 'if' statement is to check for such a situation and make sure
        // we get a proper split between
        // actual response body and actual response headers both coming from the web
        // server.
        while ('HTTP/' == substr($this->response->data, 0, 5))
        {
          list($response, $this->response->data) = preg_split("/\r\n\r\n|\n\n|\r\r/", $this->response->data, 2);
        }

        $response = preg_split("/\r\n|\n|\r/", $response);

        // Parse the response status line.
        list($protocol, $code, $status_message) = explode(' ', trim(array_shift($response)), 3);
        $this->response->protocol = $protocol;
        $this->response->status_message = $status_message;
      
        $this->response->headers = array();
      
        // Parse the response headers.
        while ($line = trim(array_shift($response)))
        {
          list($name, $value) = explode(':', $line, 2);
          $name = strtolower($name);

          if (isset($this->response->headers[$name]) && $name == 'set-cookie')
          {
            // RFC 2109: the Set-Cookie response header comprises the token Set-
            // Cookie:, followed by a comma-separated list of one or more cookies.
            $this->response->headers[$name] .= ',' . trim($value);
          }
          else
          {
            $this->response->headers[$name] = trim($value);
          }
        }

        $responses = array(
          100 => 'Continue',
          101 => 'Switching Protocols',
          200 => 'OK',
          201 => 'Created',
          202 => 'Accepted',
          203 => 'Non-Authoritative Information',
          204 => 'No Content',
          205 => 'Reset Content',
          206 => 'Partial Content',
          300 => 'Multiple Choices',
          301 => 'Moved Permanently',
          302 => 'Found',
          303 => 'See Other',
          304 => 'Not Modified',
          305 => 'Use Proxy',
          307 => 'Temporary Redirect',
          400 => 'Bad Request',
          401 => 'Unauthorized',
          402 => 'Payment Required',
          403 => 'Forbidden',
          404 => 'Not Found',
          405 => 'Method Not Allowed',
          406 => 'Not Acceptable',
          407 => 'Proxy Authentication Required',
          408 => 'Request Time-out',
          409 => 'Conflict',
          410 => 'Gone',
          411 => 'Length Required',
          412 => 'Precondition Failed',
          413 => 'Request Entity Too Large',
          414 => 'Request-URI Too Large',
          415 => 'Unsupported Media Type',
          416 => 'Requested range not satisfiable',
          417 => 'Expectation Failed',
          500 => 'Internal Server Error',
          501 => 'Not Implemented',
          502 => 'Bad Gateway',
          503 => 'Service Unavailable',
          504 => 'Gateway Time-out',
          505 => 'HTTP Version not supported',
        );

        // RFC 2616 states that all unknown HTTP codes must be treated the same as the
        // base code in their class.
        if (!isset($responses[$code]))
        {
          $code = floor($code / 100) * 100;
        }

        $this->response->code = $code;

        switch ($code)
        {
          case 200:
            // OK.
          case 201:
            // OK.
            break;

          default:
            return false;
        }

        curl_close($ch);

        return true;
    }

    public function addUser($username, $password, $name = null, $email = null)
    {
        $domtree = new DOMDocument('1.0', 'UTF-8');
        $userRoot = $domtree->createElement("user");
        $userRoot = $domtree->appendChild($userRoot);

        $userRoot->appendChild($domtree->createElement('username', (string) $username));
        $userRoot->appendChild($domtree->createElement('password', (string) $password));

        if (isset($name))
        {
          $userRoot->appendChild($domtree->createElement('name', (string) $name));
        }

        if (isset($email))
        {
          $userRoot->appendChild($domtree->createElement('email', (string) $email));
        }

        $domtree->xmlStandalone = true;

        $this->options['data'] = (string) $domtree->saveXML();
        $this->options['method'] = 'POST';
        $this->path = 'users';

        unset($domtree, $currentUser, $xmlRoot, $currentProperties, $property, $currentProperty);

        return $this->doRequest();
    }

    public function getGroup($username)
    {
        $this->options['data'] = '';
        $this->options['method'] = 'GET';
        $this->path = 'users/' . $username . '/groups';

        if ($this->doRequest())
        {
            return (string) $this->response->data;
        }
        else
        {
            return false;
        }
    }

    public function deleteGroup($username, $groups)
    {
        $domtree = new DOMDocument('1.0', 'UTF-8');
        $groupRoot = $domtree->createElement("groups");
        $groupRoot = $domtree->appendChild($groupRoot);

        foreach($groups as $group)
        {
            $groupRoot->appendChild($domtree->createElement('groupname', (string) $group));
        }

        $domtree->xmlStandalone = true;

        $this->options['data'] = (string) $domtree->saveXML();
        $this->options['method'] = 'DELETE';
        $this->path = 'users/' . $username . '/groups';

        return $this->doRequest();
    }

    public function addGroup($username, $groups)
    {
        $domtree = new DOMDocument('1.0', 'UTF-8');
        $groupRoot = $domtree->createElement("groups");
        $groupRoot = $domtree->appendChild($groupRoot);

        foreach($groups as $group)
        {
            $groupRoot->appendChild($domtree->createElement('groupname', (string) $group));
        }

        $domtree->xmlStandalone = true;

        $this->options['data'] = (string) $domtree->saveXML();
        $this->options['method'] = 'POST';
        $this->path = 'users/' . $username . '/groups';

        return $this->doRequest();
    }

    public function getUsers()
    {
        $this->options['data'] = '';
        $this->options['method'] = 'GET';
        $this->path = 'users/';

        if ($this->doRequest())
        {
            return (string) $this->response->data;
        }
        else
        {
            return false;
        }
    }

    public function getUser($username)
    {
        $this->options['data'] = '';
        $this->options['method'] = 'GET';
        $this->path = 'users/' . $username;

        return $this->doRequest();
    }

    public function deleteUser($username)
    {
        $this->options['data'] = '';
        $this->options['method'] = 'DELETE';
        $this->path = 'users/' . $username;

        return $this->doRequest();
    }

    public function updateUser($username, $password = null, $name = null, $email = null)
    {
        $domtree = new DOMDocument('1.0', 'UTF-8');
        $userRoot = $domtree->createElement("user");
        $userRoot = $domtree->appendChild($userRoot);

        $userRoot->appendChild($domtree->createElement('username', (string) $username));

        if (isset($password))
        {
          $userRoot->appendChild($domtree->createElement('password', (string) $password));
        }

        if (isset($name))
        {
          $userRoot->appendChild($domtree->createElement('name', (string) $name));
        }

        if (isset($email))
        {
          $userRoot->appendChild($domtree->createElement('email', (string) $email));
        }

        $domtree->xmlStandalone = true;

        $this->options['data'] = (string) $domtree->saveXML();
        $this->options['method'] = 'PUT';
        $this->path = 'users/' . $username;

        unset($domtree, $currentUser, $xmlRoot, $currentProperties, $property, $currentProperty);

        return $this->doRequest();
    }

    public function __construct() {	}

    public function __get($name)
    {
        if (array_key_exists($name, $this->settings)) {
            return $this->settings[$name];
        }

        return null;
    }

    public function __set($name, $value)
    {
        $this->settings[$name] = $value;
    }
}
