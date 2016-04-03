About
=====

Connection status is a pair of simple PHP scripts developed to monitor your connectivity to the Internet. It works by 
pinging an endpoint hosted in a webserver outside your local network. At this same webserver, there will be another 
script running, checking the last received pings and registering failures, in case no new pings are received within the 
specified threshold.

Usage
=====

Installation
------------
You'll need to host the scripts in a webserver outside your network. Don't forget to run `composer install` so you can 
get all the required dependencies. If you can't install composer on your webserver, run it locally and copy the `vendor` 
directory together with the scripts.

If you use Apache, don't forget to copy the `.htaccess` file, otherwise your `.env` configuration file may end up 
publicly visible. If you use another webserver, don't forget to make sure that the configuration file is not acessible.

Inside the `sql` directory you will find SQL scripts to the create the tables used by the scripts. **You don't need to keep 
this in your webserver**.

Configuration
-------------
Rename `.env.example` to `.env` and set your database credentials. The **AUTH_TOKEN** option should be set with a secret 
token that only you know, and it should be sent with every request made to scripts. The **LAST_PING_THRESHOLD** is used 
by check_failure.php so it will only register a failure if the time of the last ping is longer than the number of minutes set 
 in **LAST_PING_THRESHOLD**.
 
Running the scripts
-------------------

The easier way to run the scripts is by using cron jobs. Here is how you could run the `ping.php` from inside your local 
network:

```
* * * * * curl -XPOST "example.com/connection_status/ping.php" -d "connection=GVT" -d "auth_token=YOUR_AUTH_TOKEN"
```

This will ping your server every minute. The `connection` option is required and should contain an identifier name of 
your Internet connection. With this, you can monitor multiple connections.

On your webserver, you should create another cron job to run `check_failure.php` wich will check the last received pings 
and register failures:

```
* * * * * curl -XPOST "example.com/connection_status/check_failure.php" -d "auth_token=YOUR_AUTH_TOKEN"
```

Note that the check_failure script doesn't have the connection parameter. It always check failures for everything 
connection.

Visualization
-------------
Currently, the only way to visualize the registered failures is by querying your database.