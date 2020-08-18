# Build production environment

## Apache HTTPd as HTTP server

```puml
@startuml
digraph G {
    rankdir=LR

    www [shape=doublecircle]
    apache [label="Apche HTTPd", shape=component, fillcolor=lightblue, style=filled]
    phpfpm [label="PHP-FPM", shape=box, fillcolor=lightblue, style=filled]
    symfony [label="Symfony", shape=oval]

    www -> apache -> phpfpm -> symfony
}
@enduml
```

```puml
@startuml
digraph G {
    rankdir=LR

    www [shape=doublecircle]
    apache [label="Apche HTTPd\n+mod_php", shape=component, fillcolor=lightblue, style=filled]
    symfony [label="Symfony", shape=oval]

    www -> apache -> symfony
}
@enduml
```

## Apache HTTPd + mod_php

### Apache

#### Installation

##### Alpine

```shell script
sudo apk add apache2 php7-apache2
```

##### Debian

```shell script
sudo apt install libapache2-mod-php
```

##### CentOS

```shell script
sudo yum install httpd mod_php72
```

#### Configuration

```apacheconfig
<IfModule mod_ssl.c>
    <VirtualHost *:443>
        ServerName domain.tld
        ServerAlias www.domain.tld

        DocumentRoot /var/www/livres/public

        ErrorLog ${APACHE_LOG_DIR}/error-livres.log
        CustomLog ${APACHE_LOG_DIR}/access-livres.log combined

        SSLEngine on

        SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        <Directory /var/www/livres/public>
            AllowOverride None
            Order Allow,Deny
            Allow from All

            FallbackResource /index.php
        </Directory>

        <Directory /srv/app/public/bundles>
            FallbackResource disabled
        </Directory>
        <Directory /srv/app/public/cover>
            FallbackResource disabled
        </Directory>
        <Directory /srv/app/public/images>
            FallbackResource disabled
        </Directory>
        <Directory /srv/app/public/js>
            FallbackResource disabled
        </Directory>

        <FilesMatch "\.(cgi|shtml|phtml|php)$">
            SetEnv APP_ENV prod
            SetEnv DATABASE_URL <app-db-dsn>
            SetEnv APP_SECRET <app-secret-id>
            SetEnv REDISEARCH_URL <app-redisearch-dsn>

            SSLOptions +StdEnvVars
        </FilesMatch>
        <Directory /usr/lib/cgi-bin>
            SSLOptions +StdEnvVars
        </Directory>
    </VirtualHost>
</IfModule>
```

(Base on [Symfony documentation](https://symfony.com/doc/current/setup/web_server_configuration.html#apache-with-mod-php-php-cgi))

## Apache HTTPd + FPM

To work with Apache HTTPd you need to have Apache HTTPd and PHP-FPM

### Apache HTTPd

#### Installation

##### Alpine

```shell script
sudo apk add apache2
```

##### Debian

```shell script
sudo apt install apache2
```

##### CentOS

```shell script
sudo yum install httpd
```

#### Configuration

```
a2enmod proxy_fcgi setenvif
a2enconf php7.3-fpm
```



```apacheconfig
<IfModule mod_ssl.c>
    <VirtualHost *:443>
        ServerName domain.tld
        ServerAlias www.domain.tld
    
        <FilesMatch \.php$>
            SetHandler proxy:unix:/path/to/fpm.sock|fcgi://dummy
            SetEnv APP_ENV prod
            SetEnv DATABASE_URL <app-db-dsn>
            SetEnv APP_SECRET <app-secret-id>
            SetEnv REDISEARCH_URL <app-redisearch-dsn>
        </FilesMatch>
    
        DocumentRoot /var/www/livres/public
        
        <Directory /var/www/livres/public>
            AllowOverride None
            Require all granted
            FallbackResource /index.php
        </Directory>

        ErrorLog /var/log/apache2/livres_error.log
        CustomLog /var/log/apache2/livres_access.log combined
    </VirtualHost>
</IfModude>
```

(Base on [Symfony documentation](https://symfony.com/doc/current/setup/web_server_configuration.html#using-mod-proxy-fcgi-with-apache-2-4))

### PHP-FPM

#### Installation

##### Alpine
```shell script
sudo apk add php7 php7-fpm php7-opcache \
    php7-bcmath php7-ctype php7-curl php7-dom php7-gd php7-iconv php7-intl php7-json php7-mbstring php7-pdo php7-simplexml php7-sodium php7-xml php7-xsl php7-zip 
```

##### Debian
```shell script
sudo apt install php7.3 php7.3-fpm php7.3-opcache \
    php7.3-bcmath php7.3-ctype php7.3-curl php7.3-gd php7.3-iconv php7.3-intl php7.3-json php7.3-mbstring php7.3-pdo php7.3-xml php7.3-xsl php7.3-zip 
```

##### CentOS
```shell script
sudo yum install php php-fpm php-opcache \
    php-bcmath php-ctype php-curl php-gd php-iconv php-intl php-json php-mbstring php-pdo php-xml php-xsl php-zip 
```

#### Configuration

PHP-FPM is preconfigure, and in most cases, default configuration is enough.