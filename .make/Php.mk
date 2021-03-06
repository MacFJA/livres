ifeq ($(findstring .make/DockerCompose.mk, $(MAKEFILE_LIST)),)
  include .make/DockerCompose.mk
endif

SHELL := /bin/bash

.PHONY: install analyze fix-code clean fix-code-php install-fixture analyze-php composer

# Global Target

install:: | vendor
	@:
analyze:: analyze-php
	@:
fix-code:: fix-code-php
	@:
clean::
	@echo Remove all generated files
	rm -f composer.phar
	rm -f .php_cs.cache
	@echo Remove all generated directories
	rm -rf vendor
	rm -rf var/cache
	rm -rf var/log
	rm -rf build
	rm -rf public/bundles
	rm -rf public/cover
	rm -rf public/images

# Target

fix-code-php: | vendor
	$(COMPOSER) install --optimize-autoloader --no-suggest --prefer-dist --no-scripts
	$(COMPOSER) normalize
	$(COMPOSER) exec -v php-cs-fixer -- fix
	@#$(COMPOSER) exec -v psalm -- --alter --issues=all src

install-fixture: docker-compose.yml .env.dev | vendor
	$(DKR_COMP) exec php php /srv/app/bin/console doctrine:schema:drop --force
	$(DKR_COMP) exec php php /srv/app/bin/console doctrine:schema:update --force
	$(DKR_COMP) exec php php /srv/app/bin/console doctrine:fixtures:load

analyze-php: docker-compose.yml | vendor
	$(COMPOSER) exec -v parallel-lint -- src
	$(COMPOSER) exec -v php-cs-fixer -- fix --dry-run
	$(COMPOSER) exec -v unused_scanner -- .unused.php
	$(COMPOSER) exec -v security-checker -- security:check
	$(COMPOSER) exec -v phpcpd -- --fuzzy src
	$(COMPOSER) exec -v phpmd -- src ansi phpmd.xml
	$(COMPOSER) exec -v phpa -- src
	$(COMPOSER) exec -v phpstan -- analyse --level=8 --memory-limit=256M src
	$(COMPOSER) exec -v psalm -- --show-info=true src
	$(COMPOSER) exec -v phan -- --allow-polyfill-parser --color --color-scheme=light --output-mode=text


# Files

vendor:
ifneq (prod,${BUILD_MODE})
	$(COMPOSER) install --optimize-autoloader
else
	APP_ENV=prod $(COMPOSER) install --optimize-autoloader --no-dev --no-suggest --prefer-dist
endif

composer.phar:
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php composer-setup.php --quiet
	rm composer-setup.php

# Check Composer installation
ifneq ($(shell command -v composer > /dev/null ; echo $$?), 0)
  ifneq ($(MAKECMDGOALS),composer.phar)
    $(shell $(MAKE) composer.phar)
  endif
  COMPOSER=php composer.phar
else
  COMPOSER=composer
endif

# Magic Command

ifeq ($(firstword $(MAKECMDGOALS)),composer)
  COMPOSER_ARGS=$(wordlist 2, $(words $(MAKECMDGOALS)), $(MAKECMDGOALS))
  $(eval $(COMPOSER_ARGS):;@:)
endif
composer:
	$(COMPOSER) $(COMPOSER_ARGS)