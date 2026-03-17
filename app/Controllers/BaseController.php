<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $db;
    protected $session;
    protected $load;
    protected $input;
    protected $uri;
    protected $security;
    protected $helpers = ['url', 'form', 'file', 'my'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->db = \Config\Database::connect();
        $this->session = \Config\Services::session();
        $this->security = \Config\Services::security();

        // Compatibility Load Mock
        $this->load = new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function view($name, $data = [], $return = false) {
                if ($return) return view($name, $data);
                echo view($name, $data);
            }
            public function model($name) {
                $name = strtolower($name);
                $className = "App\\Models\\" . ucfirst($name);
                if (!class_exists($className)) {
                    // Try with prefix m_
                    $className = "App\\Models\\" . ucfirst($name);
                }
                $this->parent->$name = new $className();
            }
            public function library($name, $params = null) {
                if (strtolower($name) === 'myphpmailer') {
                    require_once APPPATH . 'Libraries/MyPHPMailer.php';
                    $this->parent->myphpmailer = new \MyPHPMailer();
                }
            }
            public function helper($name) {
                helper($name);
            }
        };

        // Compatibility Input Mock
        $this->input = new class($request) {
            private $request;
            public function __construct($request) { $this->request = $request; }
            public function post($index = null, $xss_clean = false) {
                return $this->request->getPost($index);
            }
            public function get($index = null, $xss_clean = false) {
                return $this->request->getGet($index);
            }
        };

        // Compatibility URI Mock
        $this->uri = new class($request) {
            private $request;
            public function __construct($request) { $this->request = $request; }
            public function segment($n) {
                $segments = $this->request->getUri()->getSegments();
                return isset($segments[$n-1]) ? $segments[$n-1] : null;
            }
        };
    }
}
