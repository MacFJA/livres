# Build production environment

## Sqlite as Database

```puml
@startuml
digraph G {
    rankdir=LR

    symfony [label="Symfony", shape=oval]
    sqlite [label="Sqlite", fillcolor=lightblue, style=filled, shape=cylinder]

    symfony -> sqlite
}
@enduml
```

## Sqlite

### Installation

#### Alpine

```shell script
sudo apk add php7-sqlite3
```

#### Debian

```shell script
sudo apt install php-sqlite3
```

#### CentOS

```shell script
sudo yum install php-sqlite
```

### Symfony configuration

You need to tell symfony where to find the database.
You have two options: use Environment variable, use `dotenv` file.

#### Environment variable

Edit your HTTP server configuration to add environment variable
```ini
DATABASE_URL=sqlite:///%kernel.project_dir%/var/app.db
```

#### Dotenv configuration

At the project root create/edit the file `.env.local` and add the following line:
```ini
DATABASE_URL=sqlite:///%kernel.project_dir%/var/app.db
```
