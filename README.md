# Módulo ASAP Log para Magento 2.0 ou superior

Abra sua conta gratuita com a gente e comece seus envios! Acesse https://painel.asaplog.com.br/abra-sua-conta.

Com este módulo você poderá oferecer o frete de forma nativa em sua loja. Basta instalar e configurar sua chave de integração.

**Importante:** Recomendamos que seja feito backup dos arquivos e banco de dados de sua loja antes de proceder.

## Instalação

### Método 1 - Arquivo ZIP

Baixe o módulo (https://github.com/asaplog/magento2/archive/master.zip) no servidor.

Na pasta do Magento, abra as pastas ```app/code/```. Crie então uma pasta chamada ```ASAPLog```, e dentro dela outra pasta chamada ```Cotacao```. A árvore de pastas ficará assim: ```app/code/ASAPLog/Magento/```.

Extraia o arquivo ZIP dentro dessa pasta.

### Método 2 - Composer

No terminal, vá até a pasta do Magento e execute:

```
composer require "asaplog/cotacao @dev"
```

## Configuração

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

Vá até as configuração de frete do Magento em **Lojas > Configuração > Vendas > Métodos de Envio**.

Nesta tela, você deve encontrar a opção ASAP Log, preencher o Código de Integração fornecido e salvar.

Tudo pronto! Agora você deve testar uma cotação para validar a instalação.

Lembrando que o CEP do destinatário deve estar em nossa àrea de atendimento, o peso e o valor não devem exceder sua negociação e os produtos devem ter peso (Kg) cadastrado.

## Monitoramento

Você pode habilitar a opção **Registrar chamadas** na tela de configuração e consultar o arquivo ```var/log/asaplog_cotacao.log``` na pasta do Magento para verificar se houveram erros.
