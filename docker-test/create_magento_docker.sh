COMPOSE_HTTP_TIMEOUT=200 docker-compose up -d
echo 'next command takes at most about 15min to finish, dont worry!!!'
sleep 20
docker exec -it magento_web install-sampledata
sleep 20
docker exec -it magento_web install-magento
docker exec -it magento_web apt install unzip
