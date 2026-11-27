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
    protected $helpers = ['url', 'form', 'file', 'my', 'rbac'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->db = \Config\Database::connect('default');
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
                $name = strtolower($name);
                if ($name === 'myphpmailer') {
                    require_once APPPATH . 'Libraries/MyPHPMailer.php';
                    $this->parent->myphpmailer = new \MyPHPMailer();
                } else if ($name === 'upload') {
                    $this->parent->upload = new class($this->parent, $params) {
                        private $parent;
                        private $config;
                        private $uploadData = [];
                        private $errors = '';

                        public function __construct($parent, $config) {
                            $this->parent = $parent;
                            $this->config = $config;
                        }

                        public function do_upload($field = 'userfile') {
                            $request = \Config\Services::request();
                            $file = $request->getFile($field);
                            if (!$file || !$file->isValid()) {
                                $this->errors = $file ? $file->getErrorString() : 'No file uploaded';
                                return false;
                            }

                            // Ambil properti file SEBELUM $file->move(),
                            // karena file temporary di tmp/ akan hilang setelah dipindahkan
                            $clientName = $file->getClientName();
                            $clientMime = $file->getClientMimeType();
                            $clientExt  = strtolower((string) $file->getClientExtension());
                            $realMime   = strtolower((string) $file->getMimeType());
                            $clientSize = $file->getSizeByUnit('kb');
                            $isImage    = strpos($realMime, 'image') !== false;

                            // Tentukan ekstensi aman saat file temp masih ada
                            $ext = strtolower((string) ($file->getExtension() ?: $clientExt ?: pathinfo($clientName, PATHINFO_EXTENSION)));

                            // 1. Tolak mutlak ekstensi berbahaya
                            $blockedExts = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8', 'htaccess', 'sh', 'exe', 'bat', 'cmd', 'js', 'svg', 'html', 'htm', 'jsp', 'asp', 'aspx', 'py', 'pl', 'cgi'];
                            if ($ext === '' || in_array($ext, $blockedExts, true)) {
                                $this->errors = 'Jenis berkas tidak diizinkan demi keamanan.';
                                return false;
                            }

                            // 2. Validasi whitelist allowed_types
                            $allowedStr   = strtolower((string) ($this->config['allowed_types'] ?? 'jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx'));
                            $allowedTypes = array_values(array_filter(array_map('trim', explode('|', $allowedStr))));
                            if (!in_array($ext, $allowedTypes, true)) {
                                $this->errors = 'Ekstensi berkas .' . $ext . ' tidak diizinkan.';
                                return false;
                            }

                            // 3. Validasi MIME type asli dari isi file
                            $mimeMap = [
                                'jpg'  => ['image/jpeg', 'image/pjpeg'],
                                'jpeg' => ['image/jpeg', 'image/pjpeg'],
                                'png'  => ['image/png'],
                                'gif'  => ['image/gif'],
                                'pdf'  => ['application/pdf'],
                                'doc'  => ['application/msword', 'application/octet-stream', 'application/cdfv2'],
                                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
                                'xls'  => ['application/vnd.ms-excel', 'application/octet-stream', 'application/cdfv2'],
                                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
                                'csv'  => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
                            ];
                            $allowedMimes = $mimeMap[$ext] ?? [];
                            if (empty($allowedMimes) || !in_array($realMime, $allowedMimes, true)) {
                                $this->errors = 'Tipe konten berkas tidak sesuai dengan ekstensi .' . $ext . '.';
                                return false;
                            }

                            // 4. Validasi ukuran maksimal (dalam KB)
                            $maxKb = (int) ($this->config['max_size'] ?? 10240);
                            if ($maxKb > 0 && $clientSize > $maxKb) {
                                $this->errors = "Ukuran berkas melebihi batas maksimal ({$maxKb} KB).";
                                return false;
                            }

                            $path = $this->config['upload_path'] ?? './upload';

                            // Handle CI3 style relative path
                            if (strpos($path, './') === 0) {
                                $path = FCPATH . substr($path, 2);
                            }

                            // Pastikan folder tujuan upload ada dengan permission aman
                            if (!is_dir($path)) {
                                mkdir($path, 0755, true);
                            }

                            $newName = $file->getRandomName();
                            if (!empty($this->config['file_name'])) {
                                $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $this->config['file_name']);
                                if ($safeBase !== '') {
                                    $newName = $safeBase . '.' . $ext;
                                }
                            }

                            if ($file->move($path, $newName)) {
                                $this->uploadData = [
                                    'file_name'   => $newName,
                                    'file_type'   => $realMime ?: $clientMime,
                                    'file_path'   => $path,
                                    'full_path'   => rtrim($path, '/\\') . '/' . $newName,
                                    'raw_name'    => pathinfo($newName, PATHINFO_FILENAME),
                                    'orig_name'   => $clientName,
                                    'client_name' => $clientName,
                                    'file_ext'    => '.' . $ext,
                                    'file_size'   => $clientSize,
                                    'is_image'    => $isImage,
                                ];
                                return true;
                            }
                            $this->errors = $file->getErrorString() ?: 'Gagal menyimpan berkas unggahan.';
                            return false;
                        }

                        public function data($item = null) {
                            return $item ? ($this->uploadData[$item] ?? null) : $this->uploadData;
                        }

                        public function display_errors() {
                            return $this->errors;
                        }
                    };
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

        // --- GLOBAL DATA SHARING ---
        // Cache static instansi config in session to eliminate remote DB query latency on every HTTP request
        if (!$this->session->has('cached_instansi_config')) {
            $instansi = $this->db->query("SELECT * FROM tr_instansi LIMIT 1")->getRow();
            $this->session->set('cached_instansi_config', $instansi ?: '__EMPTY__');
        } else {
            $cached = $this->session->get('cached_instansi_config');
            $instansi = ($cached === '__EMPTY__') ? null : $cached;
        }
        
        // Share with all views globally
        $GLOBALS['instansi_config'] = $instansi; 
    }
}
