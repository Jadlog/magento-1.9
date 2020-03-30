#!/usr/bin/env bash
bold=$(tput bold)
normal=$(tput sgr0)

echo "build.sh - uso: "
echo "    Para apenas gerar o pacote jadlog-magento.zip: "
echo "      ./build.sh"
echo "    Para gerar o pacote jadlog-magento.zip e copiar o código para o container docker: "
echo "      ./build.sh <nome do container>"

rm package/jadlog-magento.zip
cd src
zip -r ../package/jadlog-magento.zip *
cd ..
unzip -l package/jadlog-magento.zip
pac=`ls -a package/jadlog-magento.zip`
echo -e "Pacote gerado em: ${bold}$pac${normal}"

#atualizar plugin
if [ ! -z "$1" ]; then
  echo "Copiando arquivos para o docker..."
  docker cp package/jadlog-magento.zip $1:/var/www/html/
  echo "Descompactar plugin..."
  docker exec -it $1 bash -c "cd /var/www/html/; unzip -o jadlog-magento.zip"
  echo "Ajustar permissões..."
  docker exec -it $1 bash -c "chown -R www-data.www-data /var/www/html"
  echo "Apagar cache..."
  docker exec -it $1 bash -c "rm -Rf /var/www/html/var/cache/*"
  echo "Apagar session..."
  docker exec -it $1 bash -c "rm -Rf /var/www/html/var/session/*"
  echo "Ok!"
fi
