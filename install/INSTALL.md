# Installation instructions

The court booking webside uses the MDB2 database plugin in the PEAR framework
for communicating with the database backend. Detailed instructions are (here)
[phttp://pear.php.net/manual/en/installation.getting.php], but the general
approach is to download go-pear.phar and run this file with PHP

		$ wget http://pear.php.net/go-pear.phar
		$ php go-pear.phar
		$ /home/leif/pear/bin/pear install MDB2#mysql

Next we must create the MYSQL database and user account

		$ mysql -u root -p
		mysql> CREATE DATABASE courtBooking;
		mysql> GRANT ALL PRIVILEGES ON courtBooking.* TO courtBooking@localhost
		IDENTIFIED BY 'secret_password';
		mysql> quit;

Next we create the correct tables in the MYSQL database

		$ mysql -u courtBooking -p courtBooking < courtBooking.sql

Copy the base config file `inc/config.orig.php` to `inc/config.php` and change
the database username, password, database, add PEAR framework path and MOST
IMPORTANTLY changed the password salt (this is to make the storage of your
users' password more secure).

And finally edit 'setup_admin.php' and execute with PHP to create and admin user

		# php setup_admin.php

You should be able to browse to the folder within your hosting server and log
in.
