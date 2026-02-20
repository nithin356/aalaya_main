<?php
/**
 * Simple SMTP Mailer for Aalaya
 * Provides authenticated SMTP delivery to prevent emails from going to spam.
 */

class Mailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;

    public function __construct() {
        $config = parse_ini_file(dirname(__DIR__) . '/config/config.ini', true);
        $this->host = $config['email']['smtp_host'] ?? '';
        $this->port = $config['email']['smtp_port'] ?? 465;
        $this->user = $config['email']['smtp_user'] ?? '';
        $this->pass = $config['email']['smtp_pass'] ?? '';
        $this->from = $config['email']['smtp_from'] ?? '';
    }

    public function send($to, $subject, $message) {
        $newline = "\r\n";
        $timeout = 30;

        // Use SSL/TLS prefix if port is 465
        $host = ($this->port == 465) ? "ssl://{$this->host}" : $this->host;
        
        $socket = fsockopen($host, $this->port, $errno, $errstr, $timeout);
        if (!$socket) {
            error_log("SMTP Connection Error: $errstr ($errno)");
            return false;
        }

        $getResponse = function($socket) {
            $response = "";
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) == " ") break;
            }
            return $response;
        };

        $sendCommand = function($socket, $command) use ($newline, $getResponse) {
            fputs($socket, $command . $newline);
            return $getResponse($socket);
        };

        $getResponse($socket); // Initial response

        $sendCommand($socket, "EHLO " . $_SERVER['HTTP_HOST']);
        $sendCommand($socket, "AUTH LOGIN");
        $sendCommand($socket, base64_encode($this->user));
        $sendCommand($socket, base64_encode($this->pass));
        $sendCommand($socket, "MAIL FROM: <{$this->from}>");
        $sendCommand($socket, "RCPT TO: <{$to}>");
        $sendCommand($socket, "DATA");

        // Prepare Headers
        $headers = "MIME-Version: 1.0" . $newline;
        $headers .= "Content-type: text/plain; charset=utf-8" . $newline;
        $headers .= "To: <{$to}>" . $newline;
        $headers .= "From: Aalaya <{$this->from}>" . $newline;
        $headers .= "Subject: {$subject}" . $newline;
        $headers .= "Date: " . date("r") . $newline;
        $headers .= "Message-ID: <" . time() . "noreply@" . $this->host . ">" . $newline;
        $headers .= $newline;

        fputs($socket, $headers . $message . $newline . "." . $newline);
        $getResponse($socket);

        $sendCommand($socket, "QUIT");
        fclose($socket);

        return true;
    }
}
