<?php

require_once __DIR__ . '/fiskaly_helpers.php';

class FiskalyClient
{
    /** @var string */
    private $baseUrl;

    /** @var string|null */
    private $accessToken;

    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim((string) $baseUrl, '/') . '/';
        $this->accessToken = null;
    }

    /**
     * @return array
     */
    public function autenticar($apiKey, $apiSecret)
    {
        $payload = array(
            'content' => array(
                'api_key' => (string) $apiKey,
                'api_secret' => (string) $apiSecret,
            ),
        );

        $result = $this->request('POST', 'auth', $payload, false);
        if (!isset($result['content']['access_token']['bearer'])) {
            throw new Exception('FiskalyClient: respuesta de auth sin access token');
        }

        $this->accessToken = (string) $result['content']['access_token']['bearer'];

        return $result;
    }

    /**
     * @return array
     */
    public function enviarFactura($idClient, $uuidInvoice, array $jsonBody)
    {
        if ($this->accessToken === null || $this->accessToken === '') {
            throw new Exception('FiskalyClient: no autenticado');
        }

        $path = 'clients/' . rawurlencode((string) $idClient) . '/invoices/' . rawurlencode((string) $uuidInvoice);

        return $this->request('PUT', $path, $jsonBody, true);
    }

    /**
     * @return array
     */
    public function consultarFactura($idClient, $invoiceId)
    {
        if ($this->accessToken === null || $this->accessToken === '') {
            throw new Exception('FiskalyClient: no autenticado');
        }

        $path = 'clients/' . rawurlencode((string) $idClient) . '/invoices/' . rawurlencode((string) $invoiceId);

        return $this->request('GET', $path, null, true);
    }

    /**
     * @param string $method
     * @param string $path
     * @param array|null $body
     * @param bool $withAuth
     * @return array
     */
    private function request($method, $path, $body, $withAuth)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('FiskalyClient: cURL no disponible');
        }

        $url = $this->baseUrl . ltrim($path, '/');
        $ch = curl_init($url);
        if ($ch === false) {
            throw new Exception('FiskalyClient: no se pudo iniciar cURL');
        }

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );
        if ($withAuth) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        if ($body !== null) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                curl_close($ch);
                throw new Exception('FiskalyClient: error al codificar JSON');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new Exception('FiskalyClient: error cURL: ' . $curlErr);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('FiskalyClient: respuesta JSON inválida (HTTP ' . $httpCode . ')');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = 'HTTP ' . $httpCode;
            if (isset($decoded['content']['message']) && (string) $decoded['content']['message'] !== '') {
                $msg = (string) $decoded['content']['message'];
            } elseif (isset($decoded['message']) && (string) $decoded['message'] !== '') {
                $msg = (string) $decoded['message'];
            } elseif (isset($decoded['content']['error']) && (string) $decoded['content']['error'] !== '') {
                $msg = (string) $decoded['content']['error'];
            }
            if (isset($decoded['content']['code']) && (string) $decoded['content']['code'] !== '') {
                $msg .= ' [' . $decoded['content']['code'] . ']';
            }
            if (function_exists('insertErrorLog')) {
                insertErrorLog(
                    'FiskalyClient ' . strtoupper($method) . ' ' . $path . ' → ' . $msg
                    . ' | body=' . substr(isset($json) ? $json : '', 0, 2000)
                    . ' | resp=' . substr((string) $raw, 0, 1500)
                );
            }
            throw new Exception('FiskalyClient: ' . $msg);
        }

        return $decoded;
    }

    /**
     * Espera hasta REGISTERED o agota intentos (misma lógica que fiskaly_events.js).
     *
     * @return array
     */
    public function esperarRegistroFactura($idClient, $invoiceId, $maxIntentos, $intervaloSegundos)
    {
        $maxIntentos = max(1, (int) $maxIntentos);
        $intervaloSegundos = max(1, (int) $intervaloSegundos);
        $ultimo = null;

        for ($i = 1; $i <= $maxIntentos; $i++) {
            $ultimo = $this->consultarFactura($idClient, $invoiceId);
            $registration = null;
            if (isset($ultimo['content']['transmission']['registration'])) {
                $registration = $ultimo['content']['transmission']['registration'];
            }

            if ($registration !== 'PENDING') {
                return $ultimo;
            }

            if ($i < $maxIntentos) {
                sleep($intervaloSegundos);
            }
        }

        return is_array($ultimo) ? $ultimo : array();
    }
}
