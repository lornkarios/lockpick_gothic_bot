#!/bin/bash
echo "Stop container"
docker stop php
docker rm php
docker image rm lornkarios/lockpick-gothic-api-bot
echo "Pull image"
docker pull lornkarios/lockpick-gothic-api-bot
echo "Start php container"
cd /root/lockpick_gothic_bot/php
docker run -d \
  --network=lockpick_gothic_bot_network \
  --name=php \
  --restart=on-failure \
   -v ./storage:/var/www/html/storage \
  --env-file=.env \
  lornkarios/lockpick-gothic-api-bot
echo "Finish deploying!"