<?php
	/**
	 * Created by Breskott's Software House.
	 * Programmer: Victor Breskott
	 * Visit: https://breskott.com.br/
	 * Date: 30/01/2020
	 * Time: 14:38
	 */

	namespace Sinesp;


	class MandadoSinesp
	{
        private $url = 'https://cidadao.sinesp.gov.br/mandado/MandadoServiceIntegracao';
        private $proxy = '';

        private $nome = '';
        private $response = '';
        private $dados = [];

        /**
         * Time (in seconds) to wait for a response
         * @var int
         */
        private $timeout = 10;

        public function buscar($nome, array $proxy = [])
        {
            if ($proxy) {
                $this->proxy($proxy['ip'], $proxy['porta']);
            }

            $this->nome = $nome;
            $this->exec();

            return $this;
        }

        public function dados()
        {
            return $this->dados;
        }

        public function proxy($ip, $porta)
        {
            $this->proxy = $ip . ':' . $porta;
        }

        /**
         * Set a timeout for request(s) that will be made
         * @param int $seconds How much seconds to wait
         * @return self
         */
        public function timeout($seconds)
        {
            $this->timeout = $seconds;

            return $this;
        }

        public function __get($name)
        {
            return array_key_exists($name, $this->dados) ? $this->dados[$name] : '';
        }

        public function existe()
        {
            return array_key_exists('codigoRetorno', $this->dados) && $this->dados['codigoRetorno'] != '3';
        }

        private function exec()
        {
            $this->verificarRequisitos();
            $this->obterResposta();
            $this->tratarResposta();
        }

        private function obterResposta()
        {
            $xml = $this->xml();

            $headers = array(
                "Content-type: application/x-www-form-urlencoded; charset=UTF-8",
                "Accept: text/plain, */*; q=0.01",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Content-length: " . strlen($xml),
                "User-Agent: SinespCidadao / 3.0.2.1 CFNetwork / 758.2.8 Darwin / 15.0.0",
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $this->url);

            if ($this->proxy) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $this->response = curl_exec($ch);


            curl_close($ch);
        }

        private function tratarResposta()
        {
            if (!$this->response) {
                throw new \Exception('O servidor retornou nenhuma resposta!');
            }

            $response = str_ireplace(['soap:', 'ns2:'], '', $this->response);

            $xml = simplexml_load_string($response)->Body->getMandadosResponse->return;

            $this->dados = json_decode(json_encode((array)$xml), TRUE);
        }


        private function verificarRequisitos()
        {
            if (!function_exists('curl_init')) {
                throw new \Exception('Incapaz de processar. PHP requer biblioteca cURL');
            }

            if (!function_exists('simplexml_load_string')) {
                throw new \Exception('Incapaz de processar. PHP requer biblioteca libxml');
            }

            return;
        }


        private function xml()
        {
            $xml = <<<EOX
<v:Envelope xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns:d="http://www.w3.org/2001/XMLSchema" xmlns:c="http://schemas.xmlsoap.org/soap/encoding/" xmlns:v="http://schemas.xmlsoap.org/soap/envelope/">
<v:Header>
<plataforma>ANDROID</plataforma>
<ip>10.0.0.1</ip>
<latitude>0.0</latitude>
<longitude>0.0</longitude>
</v:Header><v:Body>
<n0:getMandados xmlns:n0="http://mandado.service.sinesp.serpro.gov.br/">
<nome>%s</nome>
</n0:getMandados></v:Body></v:Envelope>
EOX;

            return sprintf($xml, $this->nome);
        }

    }
