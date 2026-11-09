<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

 
class Mail extends BaseController {
    public $m_kelolakegiatan;
    public $web_model;
    public $upload;
 
 
 public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) { 
  parent::initController($request, $response, $logger); 
  // $this->load->library('MyPHPMailer');
    }
    
    public function index()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        if (!can('manage_admin')) {
            return redirect()->to('admin');
        }
        return redirect()->to('admin');
    }

    public function send()
    {
        if (!$this->session->get('admin_valid')) {
            return redirect()->to('admin/login');
        }
        if (!can('manage_admin')) {
            return redirect()->to('admin');
        }
        $email = $this->input->post('email');
        $body  = $this->input->post('body');

        require_once APPPATH . 'Libraries/PHPMailer/class.phpmailer.php';
        require_once APPPATH . 'Libraries/PHPMailer/class.smtp.php';
        $mail = new \PHPMailer();

        $mail->IsSMTP();
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";
        $mail->Host       = env('SMTP_HOST', 'ssl://smtp.bps.go.id');
        $mail->Port       = (int)env('SMTP_PORT', 465);
        $mail->Username   = env('SMTP_USER', '');
        $mail->Password   = env('SMTP_PASS', '');
        $mail->SetFrom(env('SMTP_USER', 'dda.jateng@gmail.com'), 'Jawa Tengah Dalam Angka');
        $mail->AddReplyTo("datakita1612@gmail.com", "Data Kita");
        $mail->Subject    = "Pemberitahuan DDA Online";
        $mail->Body       = $body ?? "<h1>Notifikasi DDA Online</h1>";
        $mail->AltBody    = strip_tags($body ?? "Notifikasi DDA Online");

        if (!empty($email)) {
            $mail->AddAddress($email);
        }

        if ($mail->Send()) {
            $data["message"] = "Message sent correctly!";
        } else {
            $data["message"] = "Error: " . $mail->ErrorInfo;
        }

        $data['page'] = 'sent_mail';
        return view('admin/index', $data);
    }
}