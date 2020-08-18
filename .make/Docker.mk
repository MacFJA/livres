# Docker

DOCKER=docker run

# Check Docker installation
ifneq (1,${NO_DOCKER})
  ifneq ($(shell command -v docker > /dev/null ; echo $$?), 0)
    $(error Docker must be installed)
  endif
endif