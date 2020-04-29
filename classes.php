<?php

/**
 * Class PushNotification
 *
 * @author Vagner Cardoso <vagnercardosoweb@gmail.com>
 */
class PushNotification
{
    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appKey;

    /**
     * @var string
     */
     protected $appUrl = 'https://onesignal.com/api/v1';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * PushNotification constructor.
     *
     * @param string $appId
     * @param string $appKey
     * @param string $appUrl
     */
    public function __construct($appId, $appKey, $appUrl = null)
    {
        $this->appId = $appId;
        $this->appKey = $appKey;

        if (!is_null($appUrl)) {
            $this->appUrl = $appUrl;
        }
    }

    /**
     * Envia a notificação para o onesignal
     *
     * @param array $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function sendNotification($params = [])
    {
        if (empty($params)) {
            throw new Exception("Preencha os parametros para ser enviado a notificação.");
        }

        if (empty($params['app_id'])) {
            $params['app_id'] = $this->appId;
        }

        return $this->request('post', "{$this->appUrl}/notifications", $params);
    }

    /**
     * Recupera todas notificações enviadas
     *
     * @param int $limit
     * @param int $offset
     *
     * @return mixed
     */
    public function allNotifications($limit = 100, $offset = 0)
    {
        $params = array(
            'app_id' => $this->appId,
            'limit' => $limit,
            'offset' => $offset,
        );

        return $this->request('get', "{$this->appUrl}/notifications", $params);
    }

    /**
     * Recupera uma determinada notificação
     *
     * @param string $notificationId
     *
     * @return mixed
     * @throws \Exception
     */
    public function viewNotification($notificationId)
    {
        if (empty($notificationId)) {
            throw new Exception("A notificação a ser exibida está em branco, favor preencha para continuar.");
        }

        $this->setHeaders([
            'Content-Type: application/json; charset=utf-8'
        ]);

        $params = array(
            'app_id' => $this->appId
        );

        return $this->request('get', "{$this->appUrl}/notifications/{$notificationId}", $params);
    }

    /**
     * Seta os headers da requisição
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders($headers = [])
    {
        foreach ((array) $headers as $header) {
            $this->headers[] = $header;
        }

        return $this;
    }

    /**
     * Recupera os headers da requisição
     *
     * @return array
     */
    public function getHeaders()
    {
        // Defaults headers
        $this->headers[] = "User-Agent: VCWeb Create cURL";
        $this->headers[] = "Accept-Charset: utf-8";
        $this->headers[] = "Accept-Language: pt-br;q=0.9,pt-BR";
        $this->headers[] = "Authorization: Basic {$this->appKey}";

        return $this->headers;
    }

    /**
     * Emula o http_build_query do php com arrays multimensional
     *
     * @param array  $params
     * @param string $prefix
     *
     * @return array|string
     */
    protected function http_build_curl(array $params, $prefix = null)
    {
        if (!is_array($params)) {
            return $params;
        }

        $build = [];

        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if ($prefix && $key && !is_int($key)) {
                $key = "{$prefix}[{$key}]";
            } elseif ($prefix) {
                $key = "{$prefix}[]";
            }

            if (is_array($value)) {
                $build[] = $this->http_build_curl($value, $key);
            } else {
                $build[] = $key . '=' . urlencode($value);
            }
        }

        return implode('&', $build);
    }

    /**
     * Monta toda requisição a ser enviada e trada os dados
     *
     * @param string $method
     * @param string $endPoint
     * @param array  $params
     *
     * @return mixed
     */
    protected function request($method, $endPoint, $params = [])
    {
        $method = mb_strtoupper($method, 'UTF-8');

        // Verifica se a data e array e está passada
        if (is_array($params) && !empty($params)) {
            $params = $this->http_build_curl($params);
        }

        // Trata a URL se for GET
        if ($method === 'GET') {
            $separator = '?';
            if (strpos($endPoint, '?') !== false) {
                $separator = '&';
            }

            $endPoint .= "{$separator}{$params}";
        }

        // Inicia o cURL
        $curl = curl_init();

        // Monta os options do cURL
        $options = [
            CURLOPT_URL => $endPoint,
            CURLOPT_HTTPHEADER => $this->getHeaders(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 80,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        // Verifica se não e GET e passa os parametros
        if ($method !== 'GET') {
            $options[CURLOPT_POSTFIELDS] = $params;
        }

        // Verifica se e post e seta como post
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
        }

        // Passa os options para o cURL
        curl_setopt_array($curl, $options);

        // Resultados
        $response = curl_exec($curl);
        $error = curl_errno($curl);
        curl_close($curl);

        // Verifica se tem erros
        if ($error) {
            return $error;
        }

        // Retorna a resposta
        return $response;
    }
}
