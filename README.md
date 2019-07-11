# Extensão ASAP Log para Magento 2

Com a integração de cotação de frete da ASAP Log para Magento 2, você poderá oferecer o frete nativamente dentro de sua loja.

**Importante:** Caso o CEP do destinatário não seja atendido, o peso total da cotação exceda 30kg ou o valor da cotação exceda o de sua negociação, a cotação não será feita.

## Instalação

### Baixando arquivo ZIP

Baixe a última versão da extensão (https://github.com/asaplog/magento2/archive/master.zip) em seu servidor.

Na pasta do Magento, abra as pastas ```app/code/```. Crie então uma pasta chamada ```ASAPLog```, e dentro dela outra pasta chamada ```Cotacao```. A árvore de pastas ficará assim: ```app/code/ASAPLog/Magento/```.

Extraia o arquivo ZIP dentro dessa pasta.

No terminal, vá até a pasta do Magento e execute:

```
php bin/magento setup:upgrade
```

Caso seu Magento seja compilado, também execute:

```
php bin/magento setup:di:compile
```

Para limpar o cache do Magento, execute:

```
rm -rf cache/* page_cache/* generation/*
php bin/magento cache:flush
php bin/magento cache:clean
```

### Composer

No terminal, vá até a pasta de seu Magento e execute:

```
composer install asaplog/cotacao
```

## Configuração

Após finalizar a instalação, vá até as configuração de frete de sua loja.

Em português: Lojas > Configuração > Vendas > Métodos de Envio

Em inglês: Stores > Configuration > Sales > Shipping Methods

Nesta tela, você deve achar a opção ASAP Log e preencher o código de integração fornecido no painel do cliente.

Tudo pronto! Agora todas as cotação que houverem em sua loja serão calculados para a ASAP Log juntamente com qualquer outra transportadora que houver.

## Monitoramento

Você pode consultar o arquivo ```var/log/asaplog_cotacao.log``` para verificar se houveram erros na integração.
