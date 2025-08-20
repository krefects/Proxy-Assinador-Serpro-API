Proxy desenvolvido para se comunicar com a API da serpro e gerar carimbo de tempo para o JSignPdf.


Exemplo de uso:

Abra o JSignPdf, clique na configuração de TSA/OCSP/CRL
Em URL TSA, informe o link do proxy,  por exemplo: http://localhost/assinador/index.php
usuario e senha que estão no script serpror.php

Aquivos da pasta: assinador_v2_data, está pasta deve ser colocada em um ponto privado de sua hospedagem, para não correr o risco de vazar suas keys.

tokens_login.json >  Insira neste arquivo a sua key e secret da serpro timestamp
tokens.json >  Não precisa modificar, é usado para salvar o token gerado no login.

AVISO: Não esqueça de mudar o local da pasta "assinador_v2_data".
em seguida altere o caminho da pasta no arquivo serpro.php
