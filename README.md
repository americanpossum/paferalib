# paferalib

PHP Framework for Rapid Application Development

After a couple of years of hacking this in my spare time, I feel that my API is stable enough for other people to play around with this code as well. It's released under the GPL, so feel free to use it, fork it, or do anything else that you want as long as you send me any improvements.

Basics

paferalib is a set of components for developing web applications in PHP. It has a URL resolver, object cacher, database proxy, and all of the other convenience tools that you need in 2016. It's designed for use on any host that supports PHP 5, mod_rewrite, and MySQL or SQLite (although complex applications will really benefit from MySQL). It's lightweight enough to be useful on free or cheap hosting packages where bandwidth and/or disk space is limited, and is decently fast even on a Raspberry Pi.

The main structure is as follow:

root
+ .htaccess   # Rewrites short URLs
+ index.php   # Loads the library files and resolves the URL
+ apps        # All application code and resources live here
+ cache       # Caches compiled code, pages, and database queries
+ data        # User-created images, videos, etc...
+ libs        # External libraries
+ private     # Unreadable directory from the web; useful for security logs
+ paferalib   # Main library directory

