[//]: # (To view this file use: python -m pip install --user grip; python -m grip -b "README.md")
[//]: # (https://github.com/settings/tokens)
[//]: # (vim ~/.grip/settings.py)
[//]: # (PASSWORD = 'YOUR-ACCESS-TOKEN')
[//]: # (https://github.com/naokazuterada/MarkdownTOC)
[//]: # (Many thanks to silentcast for animated gif generation: ppa:sethj/silentcast)

# Extensão de Frete Jadlog - Magento 1.9
## Ambiente de teste

<!-- MarkdownTOC -->

- [Instalação](#instalacao)
- [Acesso](#acesso)
- [Uso](#uso)
  - [Gerar pagote do plugin](#gerar-pagote-do-plugin)
  - [Copiar arquivos para o container](#copiar-arquivos-para-o-container)
  - [Alguns comandos úteis](#alguns-comandos-uteis)

<!-- /MarkdownTOC -->

<a id="instalacao"></a>
## Instalação
O ambiente de testes roda em *docker containers* criados a partir de um arquivo de configuração do tipo *docker compose*, portanto é necessário instalar tanto o *docker* quanto o *docker-compose* na sua máquina de desenvolvimento.

Antes de utilizar o script que efetivamente criará os *containers* é necessário criar o arquivo *env* com algumas configurações. Utilize o arquivo *env.sample* como modelo:
```bash
$ cp env.sample env
$ vim env
```
<sup>* *Ajuste conforme seu ambiente*</sup>

Também será necessário criar o arquivo *docker-compose.yml*. Utilize *docker-compose.yml.sample* como modelo:
```bash
$ cp docker-compose.yml.sample docker-compose.yml
$ vim docker-compose.yml
```

Depois rode:
```bash
$ ./create_magento_docker.sh
```

<a id="acesso"></a>
## Acesso
Acesse localhost na porta configurada, no exemplo **http://localhost:12811** e termine a instalação.

<a id="uso"></a>
## Uso

<a id="gerar-pagote-do-plugin"></a>
### Gerar pagote do plugin
Criar o pacote zip a partir do código fonte, compactando a pasta **src** ou utilize o script *build.sh* fornecido na raiz do repositório:
```bash
$ ./build.sh 
  adding: app/ (stored 0%)
  adding: app/locale/ (stored 0%)

..... (cont...)

-rw-r--r-- 1 XXXXXXX users 998K set 14 15:21 package/jadlog-magento.zip
Archive:  package/jadlog-magento.zip
  Length      Date    Time    Name
---------  ---------- -----   ----
        0  2018-01-30 18:51   app/
        0  2018-01-30 18:51   app/locale/
        0  2018-01-30 18:51   app/locale/pt_BR/

..... (cont ...)

---------                     -------
  1800075                     149 files
```

<a id="copiar-arquivos-para-o-container"></a>
### Copiar arquivos para o container
O pacote *unzip* deve estar instalado no container. Se o script *create_magento_docker.sh* foi utilizado ele estará instalado.

Para copiar os arquivos utilize os seguintes comandos (considerando a partir da pasta raiz do projeto):
```bash
$ docker cp package/jadlog-magento.zip magento_web:/var/www/html/
```
<sup>*Considerando o container criado com nome **magento_web***.</sup>


Alternativamente, pode-se utilizar o script *build.sh* passando como argumento o nome do container:
```bash
$ ./build.sh magento_web
```

<a id="alguns-comandos-uteis"></a>
### Alguns comandos úteis
*Considerando o container criado com nome **magento_web***.

- Limpar sessão
```bash
$ docker exec -it magento_web bash -c 'rm -rf /var/www/html/var/session/*'
```

- Limpar cache
```bash
$ docker exec -it magento_web bash -c 'rm -rf /var/www/html/var/cache/*'
```

- Reindexar
```bash
$ docker exec -it magento_web bash -c 'php -f /var/www/html/shell/indexer.php -- reindexall'
Product Attributes index was rebuilt successfully in 00:00:00
Product Prices index was rebuilt successfully in 00:00:00
Catalog URL Rewrites index was rebuilt successfully in 00:00:07
Category Products index was rebuilt successfully in 00:00:00
Catalog Search Index index was rebuilt successfully in 00:00:00
Stock Status index was rebuilt successfully in 00:00:00
Tag Aggregation Data index was rebuilt successfully in 00:00:00

```
