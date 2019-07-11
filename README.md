# Extensão ASAP Log para Magento 2

Com a integração de cotação de frete da ASAP Log para Magento 2, você poderá oferecer o frete de forma nativa em sua loja. Basta instalar e configurar sua chave de integração.

**Importante:** Caso o CEP do destinatário não seja atendido, o peso total da cotação exceda 30kg ou o valor da cotação exceda o de sua negociação, a cotação não será feita.

## Instalação

### Baixando arquivo ZIP

Baixe a última versão da extensão (https://github.com/asaplog/magento2/archive/master.zip) em seu servidor.

Na pasta do Magento, abra as pastas ```app/code/```. Crie então uma pasta chamada ```ASAPLog```, e dentro dela outra pasta chamada ```Cotacao```. A árvore de pastas ficará assim: ```app/code/ASAPLog/Magento/```.

Extraia o arquivo ZIP dentro dessa pasta.

### Composer

No terminal, vá até a pasta de seu Magento e execute:

```
composer require "asaplog/cotacao @dev"
```

## Pós instalação

Faça upgrade do Magento:

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

Limpe o cache do Magento:

```
rm -rf cache/* page_cache/* generation/*
php bin/magento cache:flush
php bin/magento cache:clean
```

Vá até as configuração de frete de sua loja (Lojas > Configuração > Vendas > Métodos de Envio).

Nesta tela, você deve encontrar a opção ASAP Log e preencher o código de integração fornecido no painel do cliente.

Tudo pronto! Todas as cotação serão calculados pela ASAP Log.

## Monitoramento

Você pode habilitar a opção **Registrar chamadas** na tela de configuração e consultar o arquivo ```var/log/asaplog_cotacao.log``` para verificar se houveram erros na integração.
