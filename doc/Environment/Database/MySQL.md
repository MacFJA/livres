# Build production environment

## MySQL as Database

```puml
@startuml
digraph G {
    rankdir=LR

    symfony [label="Symfony", shape=oval]
    mysql [label="MySQL", fillcolor=lightblue, style=filled, shape=cylinder]

    symfony -> mysql
}
@enduml
```

## MySQL

### Installation

#### Alpine

```shell script
sudo apk add mysql
```

#### Debian

```shell script
sudo apt install mysql-server
```

#### CentOS

```shell script
sudo yum install mysql-server
```

### Configuration

MySQL is preconfigure, and in most cases, default configurations is enough.

### Symfony configuration

You need to tell symfony where to find the database.
You have two options: use Environment variable, use `dotenv` file.

#### Environment variable

Edit your HTTP server configuration to add environment variable
```ini
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7
```

#### Dotenv configuration

At the project root create/edit the file `.env.local` and add the following line:
```ini
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7
```
