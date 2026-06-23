#!/bin/bash
echo "Stop container"
docker stop php
docker rm php
docker image rm lornkarios/lockpick-gothic-api-bot
echo "Pull image"
docker pull lornkarios/lockpick-gothic-api-bot
echo "Start php container"
cd /home/akov/php
docker run
  -v ./.well-known:/var/www/html/.well-known \
  lornkarios/lockpick-gothic-api-bot
echo "Finish deploying!"