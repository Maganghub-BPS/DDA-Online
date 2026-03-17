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
    
  public function index() {
 // require_once(APPPATH.'libraries/PHPMailerAutoload.php');
 // require_once(APPPATH.'libraries/class.phpmailer.php');
   //require_once(APPPATH.'libraries/class.phpmailer.class.php');
        $mail = new \PHPMailer();
        $mail->IsSMTP(); // we are going to use SMTP
        $mail->SMTPAuth   = true; // enabled SMTP authentication
        $mail->SMTPSecure = "ssl";  // prefix for secure protocol to connect to the server
        $mail->Host       = "ssl://smtp.gmail.com";      // setting GMail as our SMTP server
        $mail->Port       = 465;                   // SMTP port to connect to GMail
        $mail->Username   = "dda.jateng@gmail.com";  // user email address
        $mail->Password   = "dd4j4t3ng3300";            // password in GMail
        $mail->SetFrom('dda.jateng@gmail.com', 'Jawa Tengah Dalam Angka');  //Who is sending the email
        $mail->AddReplyTo("datakita1612@gmail.com","Data Kita");  //email address that receives the response
        $mail->Subject    = "tester mail2";
        $mail->Body      = "<h1>Hallo, hanya memastikan saja mas :)</h1>";
        $mail->AltBody    = "coba coba";
        $destino = "rizchi.ew@gmail.com"; // Who is addressed the email to
        $mail->AddAddress($destino, "Cahya Dy");

        $mail->AddAttachment("");      // some attached files
        $mail->AddAttachment(""); // as many as you want
        if($mail->Send()) {
   $data["message"] = "Message sent correctly!";
        } else {
            $data["message"] = "Error: " . $mail->ErrorInfo;
        }
        return view('sent_mail.php',$data);
    }
}