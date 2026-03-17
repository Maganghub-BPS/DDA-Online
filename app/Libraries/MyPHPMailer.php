<?php
// No direct script access check removed
class MyPHPMailer {
    public function __construct() {
        require_once(__DIR__ . '/PHPMailer/PHPMailerAutoload.php');
    }
}