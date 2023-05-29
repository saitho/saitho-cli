# saitho-cli for PHP projects

## Development

### Requirements

* PHP 7(?)
* Composer

### Build PHAR file

```
composer install
composer run-script build
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
clear
  clear:database
upload
  upload:database
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
      "databases": [
        {
          "name": "main",
          "connection": "ssh-docker",
          "connection_settings": {
            "host": "0.0.0.0",
            "user": "root",
            "container_name": "db",
            "db_user": "db",
            "db_password": "db",
            "db_name": "db"
          },
          "allowed": ["download", "upload", "clear"]
        }
      ],
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
        ]
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
