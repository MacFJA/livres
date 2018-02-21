SHELL := /bin/bash
DATE = $(shell date "+%Y-%m-%dT%H-%M-%S")

install-sys-requirement-debian:
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	php composer-setup.php
	php -r "unlink('composer-setup.php');"
	curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
	echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
	curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
	sudo apt-get update && sudo apt-get install yarn -y

install-application-requirement:
	php composer.phar install || composer install
	yarn install

build:
	docker-compose build

run: stop build install-application-requirement asset-build
	docker-compose up

run-light: stop
	docker-compose up

stop:
	docker-compose down

asset-build:
	yarn encore dev

asset-watch:
	yarn encore dev --watch

package:
	yarn encore dev
	mkdir -p build/release/${DATE}/var
	mkdir -p build/release/${DATE}/public
	mkdir -p build/release/${DATE}/public/cover
	mkdir -p build/release/${DATE}/public/images/cache
	cp -a bin build/release/${DATE}/
	cp -a config build/release/${DATE}/
	cp -a src build/release/${DATE}/
	cp -a templates build/release/${DATE}/
	cp -a translations build/release/${DATE}/
	cp -a public/build build/release/${DATE}/public/
	cp -a public/index.php build/release/${DATE}/public/index.php
	cp -a public/cover/placeholder.png build/release/${DATE}/public/cover
	cp -a composer.json build/release/${DATE}/
	cp -a composer.lock build/release/${DATE}/
	cp -a *.md build/release/${DATE}/
	mv build/release/${DATE}/src/Entity/empty.db build/release/${DATE}/var/data.db
	composer install --working-dir="build/release/${DATE}/" --no-dev --no-scripts --prefer-dist --classmap-authoritative --ignore-platform-reqs
	rm -f build/release/${DATE}/composer.*
	touch build/release/${DATE}/composer.json
	tar --remove-files -C build/release/ -czf build/release/${DATE}.tgz ${DATE}/
