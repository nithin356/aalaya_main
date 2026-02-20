<?php
/**
 * Aalaya Mailer Class
 * Handles authenticated SMTP email delivery to prevent spam.
 */

class Mailer {
    protected $config;

    public function __construct($config) {
        $this->config = $config['email'];
    }

    /**
     * Sends an email using authenticated SMTP.
     */
    public function send($to, $subject, $message) {
        $host = $this->config['smtp_host'];
        $port = $this->config['smtp_port'];
        $user = $this->config['smtp_user'];
        $pass = $this->config['smtp_pass'];
        $from = $this->config['smtp_from'];

        // If port 465, use SSL
        $socketHost = ($port == 465) ? "ssl://$host" : $host;
        
        try {
            $socket = fsockopen($socketHost, $port, $errno, $errstr, 30);
            if (!$socket) throw new Exception("Could not connect to SMTP host: $errstr ($errno)");

            $this->getResponse($socket, "220");

            fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $this->getResponse($socket, "250");

            // If port 587, use STARTTLS
            if ($port == 587) {
                fwrite($socket, "STARTTLS\r\n");
                $this->getResponse($socket, "220");
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("STARTTLS failed");
                }
                // Send EHLO again after STARTTLS
                fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                $this->getResponse($socket, "250");
            }

            fwrite($socket, "AUTH LOGIN\r\n");
            $this->getResponse($socket, "334");

            fwrite($socket, base64_encode($user) . "\r\n");
            $this->getResponse($socket, "334");

            fwrite($socket, base64_encode($pass) . "\r\n");
            $this->getResponse($socket, "235");

            fwrite($socket, "MAIL FROM: <$from>\r\n");
            $this->getResponse($socket, "250");

            fwrite($socket, "RCPT TO: <$to>\r\n");
            $this->getResponse($socket, "250");

            fwrite($socket, "DATA\r\n");
            $this->getResponse($socket, "354");

            $messageId = sprintf("<%s.%s@aalaya.info>", bin2hex(random_bytes(16)), time());
            
            $headers = "From: Aalaya <$from>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "Message-ID: $messageId\r\n";
            $headers .= "Return-Path: <$from>\r\n";
            $headers .= "X-Priority: 3 (Normal)\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "X-Mailer: AalayaMailer/1.1\r\n";

            fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
            $this->getResponse($socket, "250");

            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            // Fallback to mail() if SMTP fails
            $headers = "From: Aalaya <$from>\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8";
            return mail($to, $subject, $message, $headers);
        }
    }

    private function getResponse($socket, $expectedCode) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        if (substr($response, 0, 3) !== $expectedCode) {
            throw new Exception("SMTP Error: " . $response);
        }
        return $response;
    }
}
