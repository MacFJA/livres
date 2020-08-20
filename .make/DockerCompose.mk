ifeq ($(findstring .make/Docker.mk, $(MAKEFILE_LIST)),)
  include .make/Docker.mk
endif

.PHONY: run stop clean docker-compose

# Global target

run:: docker-compose.yml
	$(DKR_COMP) up --build --detach

stop::
	$(DKR_COMP) down

clean::
	@echo Remove all generated files
	rm -f docker/docker-compose

# Target

docker-compose: .docker/docker-compose
.docker/docker-compose:
	curl -LO https://github.com/docker/compose/releases/download/1.25.4/docker-compose-$(shell uname -s)-x86_64
	mv docker-compose-$(shell uname -s)-x86_64 $(DKR_COMP)
	chmod u+x $(DKR_COMP)

# Files

ifneq (1,${NO_DOCKER})
  # Check Docker Compose installation
  ifneq ($(shell command -v docker-compose > /dev/null ; echo $$?), 0)
    ifneq ($(MAKECMDGOALS),docker-compose)
      $(shell $(MAKE) docker-compose)
    endif
    DKR_COMP=./.docker/docker-compose $(DKR_COMP_OPTIONS)
  else
    DKR_COMP=docker-compose $(DKR_COMP_OPTIONS)
  endif

  # Check if Traefik is already running
  ifeq (,$(wildcard docker-compose-traefik.yml))
    SELF_TRAEFIK=0
  else ifeq (,$(shell $(DKR_COMP) --file docker-compose-traefik.yml ps -q))
    SELF_TRAEFIK=0
  else
    SELF_TRAEFIK=1
  endif
  ifeq ($(findstring ":80", $(shell test "$(SELF_TRAEFIK)" == "0" && curl 127.0.0.1:8080/api/entrypoints 2> /dev/null)), ":80")
    DKR_COMP_OPTIONS=
  else
    DKR_COMP_OPTIONS=--file docker-compose.yml --file docker-compose-traefik.yml
  endif

  # Check TLD
  ifeq ($(shell which getent > /dev/null 2>&1; echo $$?), 0)
    ifneq ($(shell getent livres.docker > /dev/null 2>&1; echo $$?), 0)
      $(info Hostname with TLD .docker must be mapped on 127.0.0.1)
      $(info You can add '127.0.0.1 livres.docker' in 'etc/hosts')
      $(info Or you can install 'dnsmasq' and add the following lines in the 'dnsmasq' configuration:)
      $(info address=/.docker/127.0.0.1)
      $(info listen-address=0.0.0.0)
      $(error Hostname with TLD .docker does not respond correctly)
    endif
  else ifeq ($(shell which ping > /dev/null 2>&1; echo $$?), 0)
    ifneq ($(shell ping -c 1 livres.docker > /dev/null 2>&1; echo $$?), 0)
      $(info Hostname with TLD .docker must be mapped on 127.0.0.1)
      $(info You can add '127.0.0.1 livres.docker' in 'etc/hosts')
      $(info Or you can install 'dnsmasq' and add the following lines in the 'dnsmasq' configuration:)
      $(info address=/.docker/127.0.0.1)
      $(info listen-address=0.0.0.0)
      $(error Hostname with TLD .docker does not respond correctly)
    endif
  else ifeq ($(shell which curl > /dev/null 2>&1; echo $$?), 0)
    ifneq ($(shell --head --silent --fail-early livres.docker > /dev/null 2>&1; echo $$?), 0)
      $(info Hostname with TLD .docker must be mapped on 127.0.0.1)
      $(info You can add '127.0.0.1 livres.docker' in 'etc/hosts')
      $(info Or you can install 'dnsmasq' and add the following lines in the 'dnsmasq' configuration:)
      $(info address=/.docker/127.0.0.1)
      $(info listen-address=0.0.0.0)
      $(error Hostname with TLD .docker does not respond correctly)
    endif
  else
    $(error System not handled)
  endif
endif