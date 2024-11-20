# 360.skilltech.tools

## Setup

To redirect the 360.skilltech.tools domain to your computer, add this line to your hosts file:

```
#/etc/hosts
127.0.0.1 360.skilltech.tools
```

A sample config file is located at src/config.sample.php, copy it to src/config.php and adjust its content to your needs. Especially, you need to fill all the OIDC* constants to allow this app to use an autentication server. You should modify the USER_AGENT value with a unique string representing you so that when the app download an image from the web, the remote web server recognizes you.

## Spin containers

Verify that no other running container uses the 80 and 443 ports, then run the containers:

### Dev profile

The dev profile will launch the vue app built on the fly by Vite. The nginx server will redirect requests to either the vue app or the php server as needed.

```
cd /path/to/360.skilltech.tools
docker compose --profile dev up -d
```

### Prod profile

The dev profile will launch vite to build the vue app once. The nginx server will both serve the vue static files and the php scripts.

```
cd /path/to/360.skilltech.tools
docker compose --profile prod up -d
```

### Build profile

The build profile will only launch the build process of the vue app, the resulting files will be placed in /vue-app/dist.

```
cd /path/to/360.skilltech.tools
docker compose --profile build up -d
```

### Certificates

To work properly, applications based on the A-Frame framework must be served with HTTPS. For the development you may use self signed certificates, you can generate these file with openSSL using the already running containers. Adjust the following line with the domain name you want to use (only modify the CN parameter and don't touch the filename):

```
docker exec -ti 360skilltechtools-php-dev-1 openssl req -x509 -newkey rsa:4096 -keyout /usr/share/nginx/src/360.skilltech.tools.key -out /usr/share/nginx/src/360.skilltech.tools.crt -sha256 -days 3650 -nodes -subj "/C=XX/ST=Luxembourg/CN=360.skilltech.tools"
docker exec -ti 360skilltechtools-php-dev-1 chmod ugo+rw /usr/share/nginx/src/360.skilltech.tools.key
docker exec -ti 360skilltechtools-php-dev-1 chmod ugo+rw /usr/share/nginx/src/360.skilltech.tools.crt
```

You will need to move the files 360.skilltech.tools.key and 360.skilltech.tools.crt from the /src folder to the /certs folder, then restart the containers.

### Create needed tables into the database

The first time you launch the app (see above), an empty database named "tour" is created. You need to fill it with tables. This is one way to do so:

Launch the dev profile then open the mariadb cli into the maria-db container:
```
docker exec -ti 360skilltechtools-mariadb-1 mariadb -u root --password=rootpassword tour
```

If you use the docker desktop app, just execute this line ito the mariadb container:
```
mariadb -u root --password=rootpassword tour
```

You are now into the mariadb shell, all the SQL commands you enter here will be executed into the "tour" database. We will execute the content of a file to create the tables with this command:

```
source /sql_dump/create_tables.sql
```

When it's done you can quit the mariadb shell with the ```exit``` command.

### OIDC

This app needs an OIDC server to authenticate users. To configure the identity provider, copy the file /src/config.sample.php to /src/config.php with appropriate values.


### Install PHP dependencies

We use Composer to manage PHP dependencies. If you don't have it installed on your computer you can install it in a container. To do so, ppen a bash shell in the PHP dev container (it must be running):

    docker exec -ti 360skilltechtools-php-dev-1 bash

Then install composer and the project dependencies

    apt update && apt install unzip
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    cd /usr/share/nginx && composer install


### Files permissions

The PHP server is running as the "www-data" user (number 33), then it can only access files owned by this user. The easiest way to allow PHP to read and write into the www folder is to allow everyone to do so. You can achieve that with this command line:

```
cd /path/to/360.skilltech.tools
chmod -R 777 www
```

Another solution which is cleaner is to modify the owner of the files to www-data. The group owner of the file will remain your group, but you will need to allow this group to read and write into the folder if you want to be able to do so from the host.

```
cd /path/to/360.skilltech.tools
chown -R 33:$USER
chmod -R ug+rw www
```

## Files

Files at the root path https://360.skilltech.tools
All PHP files in /www are served by the nginx server at https://360.skilltech.tools. The PHP source files are in the /src folder as not to be directly accessible by the web server.

## Branches

The main branch is where the development happens. The test branch is used to publish the application on our test server. When you merge the code from the main branch to the test branch and a Continuous Integration script is triggered to update the hosted version. To do so:

Commit everything on main :

```
git add .
git commit -am "Fix nasty bug"
git push
```

And then merge the main branch into test :

```
git checkout test
git merge main
git push
git checkout main
```

## Licences

Unless otherwise stated, source code found in this repository is distributed under the AGPL v3 licence.
Code found in www/v is distributed under the MIT Licence (Expat)

See CREDITS.md for more details about licences and contributors.
