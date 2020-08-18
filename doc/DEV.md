# Development

## Installation

### Requirements

To run the development environment you need at least:
 - **Docker**
 - **PHP** _5.3_ (to run Composer)

### Install application

To install the application simply run the commands

```shell script
make install
make app
```

### Start the application

To start the application and the server, run the following command

```shell script
make run
```

If correctly configured, the application will be available at the address https://livres.docker

## Stack

### Ops Stack

The development stack is based on [**Docker**](https://www.docker.com/) (and [**Docker Compose**](https://docs.docker.com/compose/)) and [**Traefik**](https://containo.us/traefik/).

### Backend Stack

The backend stack use:
 - [**RediSearch**](https://oss.redislabs.com/redisearch/) as search engine
 - [**Symfony**](https://symfony.com/) as code framework
 - [**ApiPlatform**](https://api-platform.com/) as API provider
 - [**SQLite**](https://www.sqlite.org/index.html) as database

### Frontend Stack

The frontend stack use [**Svelte**](https://svelte.dev/) and [**Sass (SCSS)**](https://sass-lang.com/) 

### Code quality stack

The code quality stack use:
 - For Svelte/Javascript
   - [**ESLint**](https://eslint.org/)
 - For CSS/SCSS
   - [**Stylelint**](https://stylelint.io/)
 - For PHP
   - [**PHP Parallel Lint**](https://github.com/php-parallel-lint/PHP-Parallel-Lint)
   - [**unused_scanner**](https://github.com/Insolita/unused-scanner)
   - [**PHP Copy/Paste Detector**](https://github.com/sebastianbergmann/phpcpd)
   - [**PHP Mess Detector**](https://phpmd.org/)
   - [**PHP Assumption**](https://github.com/rskuipers/php-assumptions/)
   - [**PHPStan**](https://github.com/phpstan/phpstan)
   - [**Psalm**](https://psalm.dev/)
   - [**Phan**](https://github.com/phan/phan/wiki)

## Making a modification

### Validate your code

To validate your code (to ensure that it meet the project quality requirement), you can use `analyze` option of **`make`**.
This _command_ came in several variations:
  - `analyze-php` to only check for PHP code
  - `analyze-svelte` to only check Svelte code
  - `analyze-css` to only validate CSS and SCSS code
  - `analyze` to do the three above in one go
```bash
make analyze
```

### Auto-fixing your code

You can fix some errors automatically with the `fix-code` option of **`make`**.
The command came in several variations:
 - `fix-code-php` to fix PHP coding standard as well as rearrange the `composer.json`
 - `fix-code-svelte` to fix Svelte coding standard
 - `fix-code-css` to fix CSS and SCSS coding standard
 - `fix-code` to do the three above in one go

```bash
make fix-code
```

**NOTICE:** Please be careful, auto-fixing the code will make changes in your code, some changes can alter the behavior!

## Tips

### Useful commands

#### Reindex the book search index

```shell script
docker-compose exec php bin/console book:search:reindex
```

#### Reindex the book suggestion index

```shell script
docker-compose exec php bin/console book:search:rebuild-suggestion
```

#### Create an admin user

```shell script
docker-compose exec php bin/console admin:create:user admin --role ROLE_ADMIN
```