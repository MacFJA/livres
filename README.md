# Livres

## What is _Livres_?

The goal of the _Livres_ application is to keep track of all your books.

## Installation

### Get the package

You have several way to get the application package:
 - By building it
 - Download the a pre-build archive

#### Building the package

 - Download the source from the release section of the GitHub repository, or from the main page or clone the project.
 - Install [yarn](https://yarnpkg.com/fr/docs/install)
 - Run `yarn install`
 - Run `make package`
 - Get the **`tar.gz`** archive in the `build/release` directory

#### Download a pre-build archive

 - Download the pre-build archive from the release section of the GitHub repository.

### Server requirements

To run the application you need several things:

 - PHP 7.0 <sup>[1](#req-1)</sup>
 - XML extension for PHP 7.0
 - Intl extension for PHP 7.0
 - Iconv extension for PHP 7.0
 - PDO extension for PHP 7.0
 - Sqlite extension for PHP 7.0
 - GD extension for PHP 7.0 <sup>[2](#req-2)</sup>
 - Curl
 - A server application (Apache2, NginX, Lighttpd, etc.)
 - An HTTPS configuration (for webcam scanning) <sup>[3](#req-3)</sup>

----

 - <a name="req-1">[1]</a>: PHP 7.0 is the lowest version possible, the application work on later version (at least on PHP 7.1)
 - <a name="req-2">[2]</a>: Gmagick or Imagick can also be used, you have to change the variable `liip_imagine.driver`
 in the file **`config/packages/imagine.yaml`**
 - <a name="req-2">[3]</a>: If you don't need to use the webcam barcode scanning, you can skip this requirement

### Installation on the server

Here an example with Apache2 with SSL and self signed certificate (Ubuntu 16.04).

Put the content of the package archive in somewhere on your server (will assume it's `/srv/app` in the rest of this document).
Add the following vhost to Apache2 enabled site:

```
<IfModule mod_ssl.c>
    <VirtualHost livres:443>
        ServerAdmin webmaster@localhost
        ServerName livres

        DocumentRoot /srv/app/public

        ErrorLog ${APACHE_LOG_DIR}/error-livres.log
        CustomLog ${APACHE_LOG_DIR}/access-livres.log combined

        SSLEngine on

        SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        <Directory /srv/app/public>
            AllowOverride None
            Order Allow,Deny
            Allow from All

            <IfModule mod_rewrite.c>
                Options -MultiViews
                RewriteEngine On
                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteRule ^(.*)$ index.php [QSA,L]
            </IfModule>
        </Directory>

        <Directory /srv/app/public/bundles>
            <IfModule mod_rewrite.c>
                RewriteEngine Off
            </IfModule>
        </Directory>
        <Directory /srv/app/public/cover>
            <IfModule mod_rewrite.c>
                RewriteEngine Off
            </IfModule>
        </Directory>
        <Directory /srv/app/public/images>
            <IfModule mod_rewrite.c>
                RewriteEngine Off
            </IfModule>
        </Directory>

        <FilesMatch "\.(cgi|shtml|phtml|php)$">
            SetEnv APP_ENV prod
            SetEnv DATABASE_URL sqlite:///%kernel.project_dir%/var/data.db
            SetEnv APP_SECRET 123456789abcdef
            SSLOptions +StdEnvVars
        </FilesMatch>
        <Directory /usr/lib/cgi-bin>
            SSLOptions +StdEnvVars
        </Directory>
    </VirtualHost>
</IfModule>
```

To see more configuration examples, look at the GitHub repository wiki.

## Installation as a development application

You can install and run the application locally.

### Requirements

 - [Docker](https://docs.docker.com/install/)
 - [Yarn](https://yarnpkg.com/fr/docs/install)
 - [Node](https://nodejs.org/en/download/)
 - [Composer](https://getcomposer.org/download/)

### Installation

 - Download (or clone) the source code from the GitHub repository.
 - Run `make run`

And _voilà_, the application is running [HTTPS on localhost:8443](https://localhost:8443).

(You can also find the IP of the NginX via the command
`docker inspect livres-nginx | grep -E '\s{14}"IPAddress": "([0-9\.]+)"' | grep -oE "([0-9\.]+)"`
and use the IP with https without the port `8443`)

----

If you prefer use Docker Compose you can:

 - Download (or clone) the source code from the GitHub repository.
 - Run `composer install`
 - Run `yarn install`
 - Run `docker-compose up`