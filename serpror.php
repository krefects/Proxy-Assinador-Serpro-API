<?php

class Assinador
{
    public $dir = "C:/wamp64/www/assinador/assinador_v2_data/"; /// diretorio das keys com barra no final
    public $tentativas = 0;

    public function __construct($data)
    {
        
    }

    public function getCarimboTempo()
    {

        $validUser = "3dsigner";
        $validPass = "123456";


        if ($_SERVER['PHP_AUTH_USER'] !== $validUser || $_SERVER['PHP_AUTH_PW'] !== $validPass) {
            header('HTTP/1.0 403 Forbidden');
            echo "Usuário ou senha inválidos";
            exit;
        }

        $getTokens = json_decode(file_get_contents($this->dir . "tokens.json"), true);
        $token = $getTokens['token'];

        // Configurações do endpoint TSA do Serpro
        $tsaUrl = "https://gateway.apiserpro.serpro.gov.br/apitimestamp/v1/stamps-asn1";

        // Lê o conteúdo enviado pelo JSignPdf (ASN.1 DER TimeStampQuery)
        $requestData = file_get_contents("php://input");

        if (!$requestData) {
            http_response_code(400);
            echo "Nenhum dado recebido";
            exit;
        }

        // Inicializa cURL
        $ch = curl_init($tsaUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/timestamp-query",
            "Accept: application/timestamp-reply",
            "Authorization: Bearer " . $token
        ]);

        // Executa a requisição
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        file_put_contents('retorno.txt', $response);

        if ($httpCode == 401 && $this->tentativas < 3) {
            $this->tentativas++;
            $this->autenticar();
            return $this->getCarimboTempo();
        }

        if ($response === false || $httpCode !== 200) {
            http_response_code(502);
            echo "Erro ao se comunicar com TSA: " . curl_error($ch);
            curl_close($ch);
            exit;
        }

        curl_close($ch);
        // Retorna a resposta ASN.1 diretamente para o JSignPdf
        header("Content-Type: application/timestamp-reply");
        echo $response;
        exit;
    }

    public function autenticar()
    {
        $getTokensLogin = json_decode(file_get_contents($this->dir . "tokens_login.json"), true);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://gateway.apiserpro.serpro.gov.br/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode($getTokensLogin['key'] . ':' . $getTokensLogin['secret']),
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response, true);
        if (isset($data['error_description'])) {
            return ['status' => 'error', 'message' => $data['error_description']];
        }
        $decode = json_decode($response, true);
        $token = $decode['access_token'];
        file_put_contents($this->dir . "tokens.json", json_encode(['token' => $token]));
        return [
            'status' => 'sucesso'
        ];
    }
}
