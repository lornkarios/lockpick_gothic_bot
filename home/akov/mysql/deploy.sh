docker network create -d bridge weather_bot_network
docker run -p 3306:3306 \
 --name=mysql -d \
 --restart=on-failure \
 --env-file=.env \
 -v ./data:/var/lib/mysql \
 -v ./my.cnf:/etc/mysql/conf.d/my.cnf \
 --network=weather_bot_network \
mysql:8.0.25