#!/bin/bash

ldpath="../"
if [ -d laradock ] ; then
	ldpath="."
fi

cd "$ldpath/laradock"

echo "Running docker-compose http url is http://localhost:880"

docker-compose up nginx php-fpm php-worker gearman redis


