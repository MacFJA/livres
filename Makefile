ifeq (1,$(NO_DOCKER))
  USE_LOCAL_BIN=1
endif

include .make/*

.PHONY: app build

app: install public/js public/cover/placeholder.jpg

run::
	$(DKR_COMP) exec php bin/console book:search:reindex
	$(DKR_COMP) exec php bin/console book:search:rebuild-suggestion

build:
	rm -rf build/app
	mkdir -p build/app
	cp -Rp node_modules build/app/ || :
	cp -Rp vendor build/app/ || :
	cp -Rp .make _extra assets bin config src templates .env composer.json composer.lock Makefile package.json symfony.lock webpack.config.js package-lock.json LICENSE.md build/app/
	mkdir -p build/app/public
	mkdir -p build/app/public/cover
	cp public/index.php build/app/public/
	cd build/app ; BUILD_MODE=prod $(MAKE) -B app
	rm -rf build/app/{.make,.phive,_extra,assets,node_modules,composer.phar,Makefile,webpack.config.js,package.json,package-lock.json,symfony.lock}

public/cover/placeholder.jpg: assets/placeholder.jpg
	mkdir -p public/cover/
	cp $< $@