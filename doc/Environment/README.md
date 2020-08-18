# Build production environment

````puml
@startuml
digraph G {
    www [shape=doublecircle]
    httpserver [label="HTTP server", shape=component, style=filled, fillcolor=lightblue]
    symfony [label="Symfony", shape=oval]
    redisearch [label="RediSearch", shape=box3d]
    config [label="Configuration", shape=note]
    database [label="Database", shape=cylinder, style=filled, fillcolor=lightblue]
    session [label="Session", shape=folder, style=filled, fillcolor=lightblue]
    cache [label="Cache", shape=folder, style=filled, fillcolor=lightblue]

    www -> httpserver -> symfony -> {redisearch, config, database, session, cache}
}
@enduml
````

## Changeable component

### HTTP Server

- [**Nginx + PHP-FPM**](Http/Nginx.md)
- [**Apache HTTPd + PHP-FPM**](Http/Apache.md)
- [**Apache HTTPd with mod_php**](Http/Apache.md)

(Base on [Symfony documentation](https://symfony.com/doc/current/setup/web_server_configuration.html))

### Database

- [**Sqlite**](Database/Sqlite.md)
- [**MySQL**](Database/MySQL.md)

### Session

- Redis
- Filesystem
- Relational Database
- MongoDB

See [Symfony documentation](https://symfony.com/doc/current/session/database.html)

### Cache

- APCu
- Memory
- Filesystem
- Doctrine
- Memcached
- Database
- PSR
- Redis

See [Symfony documentation](https://symfony.com/doc/current/cache.html#configuring-cache-with-frameworkbundle)

## Other components

### RediSearch

[See RediSearch documentation](RediSearch.md).

## Environment variable

The most common place to set environment variables are in the HTTP server configurations.

See [**Nginx**](Http/Nginx.md) or [**Apache**](Http/Apache.md) configurations to see examples.