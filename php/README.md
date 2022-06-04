# saitho-cli for PHP projects

## Development

### Requirements

* PHP 7(?)
* Composer

### Build PHAR file

```
composer install
./vendor/bin/box compile
```

=> saitho-cli.phar

## Commands

Commands may require additional configuration, see below.

```
build
  build:assets    
  build:css       
  build:docker 
dev
  dev:down        
  dev:start       
  dev:stop        
  dev:up
docker
  docker:down     
  docker:restart  
  docker:start    
  docker:stop     
  docker:up 
download
  download:database
  download:files
```

## Configuration

composer.json

```json
{

  "extra": {
    "saitho-cli": {
      "download": {
        "files": [
          {
            "connection": "ssh",
            "path": "/path/on/remote/server",
            "save_path": "./public",
            "recursive": true,
            "connection_settings": {
              "host": "0.0.0.0",
              "user": "root"
            }
          }
        ],
        "database": {
          "connection": "ssh-docker",
          "save_path": "./var/data/preseed/db.sql",
          "connection_settings": {
            "host": "0.0.0.0",
            "user": "root",
            "container_name": "db",
            "db_user": "db",
            "db_password": "db",
            "db_name": "db"
          }
        }
      },
      "dev": {
        "sync-db": "./var/data/preseed/db.sql",
        "mirror-dirs": {
          "var/data/preseed/fileadmin": "public/fileadmin"
        },
        "exec": {
          "up": [
            "bin/typo3cms database:updateschema 'safe'"
          ]
        }
      },
      "docker": {
        "build": {
          "image": "saitho/myimage"
        },
        "compose": {
          "prod": [
            "./.docker/docker-compose.base.yml",
            "./.docker/docker-compose.prod.yml"
          ]
        }
      },
      "typo3": {
        "extensions": {
          "my_extension": {
            "build": {
              "builder": "pnpm",
              "script": "build-css"
            }
          }
        }
      }
    }
  }
}
```
