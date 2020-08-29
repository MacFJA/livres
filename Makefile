ifeq (1,$(NO_DOCKER))
  USE_LOCAL_BIN=1
endif

include .make/*

.PHONY: app build demo run clean

app: install public/js public/cover/placeholder.jpg

run::
	$(DKR_COMP) exec php bin/console book:search:reindex
	$(DKR_COMP) exec php bin/console book:search:rebuild-suggestion

clean::
	rm -rf build/app
	rm -f .env.dev.local

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

demo: .env.dev.local
	cp tests/demo.db var/demo.db
	sed -i .orig -E 's#DATABASE_URL=.+#DATABASE_URL=sqlite:///%kernel.project_dir%/var/demo.db#' .env.dev.local
	rm -f .env.dev.local.orig
	@echo -e "\nA new database have been created, based on the top 100 of selling book.\nThere are 3 accounts:\n\t- One administrator (login: 'admin', password: 'admin')\n\t- One editor (long: 'editor', password: 'editor')\n\t- One normal user (login: 'user', password: 'user')"

.env.dev.local: .env
	cp $< $@

public/cover/placeholder.jpg: assets/placeholder.jpg
	mkdir -p public/cover/
	cp $< $@