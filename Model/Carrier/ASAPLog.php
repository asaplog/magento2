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

    protected $_cep;
    protected $_peso;
    protected $_chave;
    protected $_valor;
    protected $_cotacao;
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
        $this->_chave = $this->getConfig('carriers/asaplog/chave');
        $this->_cep = $this->formatarCep($request->getDestPostcode());
        $this->_peso = $request->getPackageWeight();
        $this->_valor = $request->getBaseCurrency()->convert($request->getPackageValue(), $request->getPackageCurrency());

        $this->logMessage("Chave: " . $this->_chave);
        $this->logMessage("Destino: " . $this->_cep);
        $this->logMessage("Peso: " . $this->_peso);
        $this->logMessage("Valor: " . $this->_valor);

        if ($this->_chave == null || $this->_chave == '') {
            $this->logMessage("Chave não cadastrada");

            informarCotacaoInvalida();
            return false;
        }

//        if ($request->getDestCountryId() != "BR") {
//            $this->logMessage("País inválido");
//            return false;
//        }

        foreach ($request->getAllItems() as $item) {
            if ($item->getWeight() == null || $item->getWeight() == '' || $item->getWeight() <= 0) {
                $this->logMessage("Produto sem peso: " . $item->getSku());
                return false;
            }
        }

        $this->_cotacao = $this->fazerCotacao();
        if ($this->_cotacao != null) {
            if ($this->_cotacao['preco'] != null && $this->_cotacao['preco'] > 0) {

                $method = $this->_rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->_name);
                $method->setMethod($this->_code);
                $method->setMethodTitle("Entrega em até " . $this->_cotacao['prazoMaximo'] . " dias úteis");
                $method->setPrice($this->_cotacao['preco']);
                $method->setCost($this->_cotacao['preco']);
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

    public function fazerCotacao()
    {
        try {
            $ch = curl_init();

            $url = 'https://app.asaplog.com.br/webservices/v1/consultarFrete?peso=' . $this->_peso . '&valor=' . $this->_valor . '&cep=' . $this->_cep;
            $this->logMessage($url);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            $headers = array();
            $headers[] = 'accessToken: ' . $this->_chave;
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

            $storeName = $this->_storeManager->getStore()->getName();
            $baseUrl = $this->_storeManager->getStore()->getBaseUrl();

            $url = 'https://app.asaplog.com.br/webservices/v1/informarCotacaoInvalida?plataforma=MAGENTO&nome=' . $storeName . '&url=' . $baseUrl;
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
