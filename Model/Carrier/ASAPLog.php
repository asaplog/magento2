<?php

namespace ASAPLog\Cotacao\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ASAPLog extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'asaplog';
    protected $_name = 'ASAP Log';
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_rateResultFactory;
    protected $_rateMethodFactory;
    protected $_log;

    public function __construct(StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig, ErrorFactory $rateErrorFactory, LoggerInterface $logger, ResultFactory $rateResultFactory, MethodFactory $rateMethodFactory, array $data = [])
    {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function getConfig($config_path)
    {
        return $this->_scopeConfig->getValue($config_path, ScopeInterface::SCOPE_STORE);
    }

    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateResultFactory->create();

        $this->_log = (bool)$this->getConfig('carriers/asaplog/log');
        $chave = $this->getConfig('carriers/asaplog/chave');
        $cep = $this->formatarCep($request->getDestPostcode());
        $peso = $request->getPackageWeight();
        $valor = $request->getBaseCurrency()->convert($request->getPackageValue(), $request->getPackageCurrency());

        $this->logMessage("Chave: " . $chave);
        $this->logMessage("Destino: " . $cep);
        $this->logMessage("Peso: " . $peso);
        $this->logMessage("Valor: " . $valor);

        if ($chave == null || $chave == '') {
            $this->logMessage("Chave não cadastrada");
            $this->informarCotacaoInvalida();
            return false;
        }

        foreach ($request->getAllItems() as $item) {
            if ($item->getWeight() == null || $item->getWeight() == '' || $item->getWeight() <= 0) {
                $this->logMessage("Produto sem peso: " . $item->getSku());
                return false;
            }
        }

        $cotacao = $this->fazerCotacao($peso, $valor, $cep, $chave);
        if ($cotacao != null) {
            if ($cotacao['preco'] != null && $cotacao['preco'] > 0) {
                $method = $this->_rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->_name);
                $method->setMethod($this->_code);
                $method->setMethodTitle("Entrega em até " . $cotacao['prazoMaximo'] . " dias úteis");
                $method->setPrice($cotacao['preco']);
                $method->setCost($cotacao['preco']);
                $result->append($method);
                return $result;
            } else {
                $this->logMessage("Preço nulo ou igual a zero");
                return false;
            }
        } else {
            $this->logMessage("Cotação nula");
            return false;
        }
    }

    public function getAllowedMethods()
    {
        return [$this->_code => $this->_name];
    }

    public function fazerCotacao($peso, $valor, $cep, $token)
    {
        try {
            $ch = curl_init();

            $url = 'https://app.asaplog.com.br/webservices/v1/consultarFrete?peso=' . $peso . '&valor=' . $valor . '&cep=' . $cep;
            $this->logMessage($url);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            $headers = array();
            $headers[] = 'accessToken: ' . $token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                return null;
            }
            curl_close($ch);

            return json_decode($result, true);
        } catch (\Exception $exception) {
            $this->logMessage('Erro ao fazer cotação: ' . $exception->getMessage());
            return null;
        }
    }

    public function informarCotacaoInvalida()
    {
        try {
            $ch = curl_init();

            $url = 'https://app.asaplog.com.br/webservices/v1/informarCotacaoInvalida?plataforma=MAGENTO&nome=' . $this->_storeManager->getStore()->getName() . '&url=' . $this->_storeManager->getStore()->getBaseUrl();
            $this->logMessage($url);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                return false;
            }
            curl_close($ch);

            return true;
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function formatarCep($cep)
    {
        $cep = trim($cep);
        $cep = preg_replace('/[^0-9\s]/', '', $cep);
        return $cep;
    }

    public function logMessage($message)
    {
        if ($this->_log == 1) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/asaplog_cotacao.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }
}
