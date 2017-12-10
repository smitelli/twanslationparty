twanslationparty
================

Takes tweets stored in a twitstash database and reposts them in hilariously
corrupted English.

by [Scott Smitelli](mailto:scott@smitelli.com)

Installation and Requirements
-----------------------------

twanslationparty requires a PHP 5.3 installation with the following extensions
installed:

*   `curl` - Required by the `twitteroauth` library for making HTTP requests
*   `mbstring` - I believe `twitter-text-php` requires this for Unicode
*   `pdo` - Provides a database interface
*   `pdo-mysql` - PDO driver for MySQL

Additionally, a live [twitstash](http://github.com/smitelli/twitstash) database
needs to exist on your server. twanslationparty will add an additional table to
that database to store state.

### To install:

1.  Set up [twitstash](http://github.com/smitelli/twitstash) and run it at least
    once to "prime" the database.

2.  `git clone --recursive https://github.com/smitelli/twanslationparty.git &&
    cd twanslationparty`

3.  Run the following query on your `twitstash` database to add a table:

        CREATE TABLE IF NOT EXISTS `twanslationparty` (
          `original_id` bigint(20) unsigned NOT NULL,
          `translated_id` bigint(20) unsigned DEFAULT NULL,
          PRIMARY KEY (`original_id`),
          KEY `translated_id` (`translated_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

    Change `twanslationparty` to something more meaningful, especially if you
    plan to run multiple instances.

4.  `cp config.ini-sample config.ini`

5.  Edit `config.ini` to suit your fancy. You'll have to put your own Twitter
    and MySQL authentication in there. Remember to set the table name to match
    the query from step 3.

6.  `./twanslationparty.sh`

Any **new** tweets that are present in the twitstash database will be mangled by
the translation API, then reposted to the configured Twitter account. Each tweet
will be stored in twanslationparty's table as it is successfully sent. Retweets
will never be sent. Location info will be sent if it is present and the target
Twitter account has this support enabled. Any tweets that become "deleted" in
the twitstash database will be deleted from the target Twitter account.

If twitstash has a huge number of unsent tweets, it's entirely likely that the
target account will saturate its daily tweet limit and stop updating. To prevent
this from happening, you can run a query to bulk-mark all existing tweets as
having been sent, even if that is not actually the case. Starting point query:

    INSERT INTO `twanslationparty` (`original_id`) (
      SELECT `id` FROM `tweets` WHERE `deleted` IS NULL AND `rt_id` = 0
    );

Any tweets added after this query runs should post in a timely manner.

The shell script is designed to never output anything, so you can add it in a
cron job without worrying about spamming root's inbox with junk. A file called
`debug.log` will be created (and appended) by the shell script. There's
generally nothing useful in that file.

Acknowledgements
----------------

This package includes Abraham Williams' `twitteroauth` library.
<https://github.com/abraham/twitteroauth>

This package also includes Matt Sanford's `twitter-text-php` library.
<https://github.com/mzsanford/twitter-text-php>
