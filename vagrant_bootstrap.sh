#!/usr/bin/env bash

sudo apt-get update
sudo apt-get install -y php5-cli php5-mysqlnd

sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'
sudo apt-get install -y mysql-server-5.5

# this database is used by the unit/manual tests
mysql --host=localhost --user=root --password=root --execute="CREATE DATABASE IF NOT EXISTS chaperone4phpTest"

# migrate the database (create the tables that are used)
cd /vagrant &&  php vendor/bin/phinx migrate -c tests/phinx.yml