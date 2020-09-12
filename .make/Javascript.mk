ifeq ($(findstring .make/Docker.mk, $(MAKEFILE_LIST)),)
  include .make/Docker.mk
endif

SHELL := /bin/bash
ifneq (1,${USE_LOCAL_BIN})
  NPM=$(DOCKER) --rm --mount type=bind,src=$(shell PWD),dst=/home/node/app -w=/home/node/app -e BUILD_MODE=${BUILD_MODE} node:14-alpine npm
  NPX=$(DOCKER) --rm --mount type=bind,src=$(shell PWD),dst=/home/node/app -w=/home/node/app -e BUILD_MODE=${BUILD_MODE} node:14-alpine npx
else
  NPM=npm
  NPX=npx
endif

.PHONY: install analyze fix-code clean fix-code-svelte analyze-svelte analyze-css fix-code-css

# Global target

install:: | node_modules public/js
	@:
analyze:: analyze-svelte analyze-css
	@:
fix-code:: fix-code-svelte fix-code-css
	@:
clean::
	@echo Remove all generated directories
	rm -rf node_modules
	rm -rf public/js/*
	@echo Remove all generated files
	rm -f .stylelintcache

# Target

analyze-svelte: $(wildcard src/Svelte/*) .eslintrc.js package.json | node_modules
	$(NPX) eslint src/Svelte

analyze-css: $(wildcard src/Svelte/*) $(wildcard assets/*.css) package.json | node_modules
	$(NPX) stylelint assets/*.css $(wildcard src/Svelte/*) --color --cache

fix-code-css: $(wildcard src/Svelte/*) $(wildcard assets/*.css) package.json | node_modules
	$(NPX) stylelint assets/*.css $(wildcard src/Svelte/*) --color --cache --fix

fix-code-svelte: $(wildcard src/Svelte/*) .eslintrc.js package.json | node_modules
	$(NPX) eslint --fix src/Svelte --rule 'import/order: off'

# Files

node_modules: package.json
	$(NPM) install --ignore-optional --prefer-offline

public/js: $(wildcard src/Svelte/*) webpack.config.js package.json assets/* translations/* | node_modules
	rm -rf public/js/*
	$(NPX) webpack