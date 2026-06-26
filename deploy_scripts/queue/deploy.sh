#!/bin/bash
echo "Stop container"
docker stop queue
docker rm queue
echo "Start queue container"
cd /root/lockpick_gothic_bot/queue
docker run -d \
  --network=lockpick_gothic_bot_network \
  --name=queue \
  --restart=on-failure \
   -v ./storage/logs:/var/www/html/storage/logs \
  --env-file=.env \
  --entrypoint=./entrypoint.sh \
  lornkarios/lockpick-gothic-api-bot
echo "Finish deploying!"