# Build production environment

## Nginx as HTTP server

```puml
@startuml
digraph G {
    rankdir=LR

    www [shape=doublecircle]
    nginx [label="Nginx", shape=component, fillcolor=lightblue, style=filled]
    phpfpm [label="PHP-FPM", shape=box, fillcolor=lightblue, style=filled]
    symfony [label="Symfony", shape=oval]

    www -> nginx -> phpfpm -> symfony
}
@enduml
```

## Components

To work with Nginx you need to have Nginx and PHP-FPM

### Nginx

#### Installation

##### Alpine

```shell script
sudo apk add nginx
```

##### Debian

```shell script
sudo apt install nginx
```

##### CentOS

```shell script
sudo yum install nginx
```

#### Configuration

```nginx
server {
    server_name domain.tld www.domain.tld;
    root /var/www/livres/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param APP_ENV prod;
        fastcgi_param APP_SECRET <app-secret-id>;
        fastcgi_param DATABASE_URL <app-db-dsn>;
        fastcgi_param REDISEARCH_URL <app-redisearch-dsn>;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/project_error.log;
    access_log /var/log/nginx/project_access.log;
}
```

(Base on [Symfony documentation](https://symfony.com/doc/current/setup/web_server_configuration.html#web-server-nginx))

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

PHP-FPM is preconfigure, and in most cases, default configurations is enough.