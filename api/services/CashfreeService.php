<?php
class CashfreeService {
    private $verificationKeys = [];
    private $paymentKeys = [];
    private $verificationEnv;
    private $paymentEnv;
    private $verificationBaseUrl;
    private $paymentBaseUrl;

    public function __construct() {
        // Load from config file (Prioritize config.ini)
        $configPath = __DIR__ . '/../../config/config.prod.ini';
        if (file_exists(__DIR__ . '/../../config/config.ini')) {
            $configPath = __DIR__ . '/../../config/config.ini';
        }

        if (file_exists($configPath)) {
            $config = parse_ini_file($configPath, true);
            
            $this->verificationKeys = [
                'id' => $config['cashfree']['verification_client_id'] ?? '',
                'secret' => $config['cashfree']['verification_client_secret'] ?? ''
            ];
            $this->paymentKeys = [
                'id' => $config['cashfree']['payment_client_id'] ?? '',
                'secret' => $config['cashfree']['payment_client_secret'] ?? ''
            ];
            
            // Allow global mode or service-specific modes
            $globalEnv = $config['cashfree']['mode'] ?? 'TEST';
            $this->verificationEnv = $config['cashfree']['verification_mode'] ?? $globalEnv;
            $this->paymentEnv = $config['cashfree']['payment_mode'] ?? $globalEnv;
        } else {
            // Fallback
            $this->verificationEnv = 'TEST';
            $this->paymentEnv = 'TEST';
        }

        $this->verificationBaseUrl = ($this->verificationEnv === 'PROD') 
            ? 'https://api.cashfree.com' 
            : 'https://sandbox.cashfree.com';
            
        $this->paymentBaseUrl = ($this->paymentEnv === 'PROD') 
            ? 'https://api.cashfree.com' 
            : 'https://sandbox.cashfree.com';
    }

    /**
     * Create Digilocker Verification URL
     * @param string $verificationId
     * @return array
     */
    public function createDigilockerUrl($verificationId, $redirectUrl) {
        $url = $this->verificationBaseUrl . '/verification/digilocker';
        
        $data = [
            'verification_id' => $verificationId,
            'document_requested' => ['AADHAAR', 'PAN'],
            'redirect_url' => $redirectUrl,
            'user_flow' => 'signup'
        ];

        return $this->makeRequest($url, $data, 'POST', 'VERIFICATION');
    }

    /**
      * Get Document from Digilocker
     * @param string $verificationId
     * @param string $docType (AADHAAR or PAN)
     * @return array
     */
    public function getDigilockerDocument($verificationId, $docType = 'AADHAAR') {
        $url = $this->verificationBaseUrl . '/verification/digilocker/document/' . $docType . '?verification_id=' . $verificationId;
        
        return $this->makeRequest($url, null, 'GET', 'VERIFICATION');
    }

    /**
     * Create Payment Order
     * @param string $orderId
     * @param float $amount
     * @param string $customerId
     * @param string $customerPhone
     * @param string $returnUrl
     * @return array
     */
    public function createOrder($orderId, $amount, $customerId, $customerPhone, $returnUrl) {
        $url = $this->paymentBaseUrl . '/pg/orders';
        
        $data = [
            'order_id' => $orderId,
            'order_amount' => $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => $customerId,
                'customer_phone' => $customerPhone
            ],
            'order_meta' => [
                'return_url' => $returnUrl
            ]
        ];

        return $this->makeRequest($url, $data, 'POST', 'PAYMENT', '2022-09-01');
    }

    private function makeRequest($url, $data, $method = 'POST', $type = 'VERIFICATION', $apiVersion = null) {
        $keys = ($type === 'PAYMENT') ? $this->paymentKeys : $this->verificationKeys;
        
        $headers = [
            'Content-Type: application/json',
            'x-client-id: ' . $keys['id'],
            'x-client-secret: ' . $keys['secret']
        ];
        
        if ($apiVersion) {
            $headers[] = 'x-api-version: ' . $apiVersion;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['status' => 'ERROR', 'message' => $err];
        }

        return json_decode($response, true);
    }
}
?>
