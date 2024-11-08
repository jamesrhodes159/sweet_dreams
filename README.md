## SETUP GUIDE

1. Download as zip on your local pc from https://github.com/jamesrhodes159/sweet_dreams.git
2. Upload the sweet_dreams.zip file to your server's root directory and extract it.
3. create mysql database on your server and update the database credentials in .env file i.e as follows:
	* DB_CONNECTION=mysql
	* DB_HOST=127.0.0.1
	* DB_PORT=3306
	* DB_DATABASE="database name here"
	* DB_USERNAME="database username here"
	* DB_PASSWORD="database password here"
4. now go to terminal in your server and then use cd command to move to your's servers public_html directory ie.
	-- cd /var/www/public_html
	and then run composer update
	then run composer intall
	then run php artisan migrate
	then run php artisan db:seed
	then run pm2 start sweet_dreams_3087.js

That's it now just put your domain url along with /api fragment to sweet dreams mobile app source code then compile your code to run it.
Note: Php version of your server must be 8.2.* ie 8.2.20 and also composer need to be installed on server.
