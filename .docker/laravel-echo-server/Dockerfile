FROM node:16-alpine

WORKDIR /var/www

COPY echo.json /var/www/echo.json
COPY laravel-echo-server.json /var/www/laravel-echo-server.json

RUN npm install -g pm2 laravel-echo-server

EXPOSE 6001

CMD ["pm2-runtime", "echo.json"]