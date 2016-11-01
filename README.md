# paferalib

PHP Framework for Rapid Application Development

After a couple of years of hacking this in my spare time, I feel that my API is stable enough for other people to play around with this code as well. It's released under the GPL, so feel free to use it, fork it, or do anything else that you want as long as you send me any improvements.

## Basics

Paferalib is a set of components for developing web applications in PHP. It has a URL resolver, object cacher, database modeler, and all of the other convenience tools that you need in 2016. It's designed for use on any host that supports PHP 5, mod_rewrite, and MySQL or SQLite (although complex applications will really benefit from MySQL). It's lightweight enough to be useful on free or cheap hosting packages where bandwidth and/or disk space is limited, and is decently fast even on a Raspberry Pi.

### Installation

You can install paferalib simply by downloading the zip file and unzipping it into your web server's www directory. Afterwards, you can point your browser to your website and setup your database, scripts, and so forth using the administration interface.

### Directory Structure

The main structure is as follow:

root
+ .htaccess   
	
	Rewrites short URLs
	
+ index.php   
	
	Loads the library files and resolves the URL
	
+ apps        

	All application code and resources live here
	
+ cache       
	
	Caches compiled code, pages, and database queries
	
+ data        

	User-created images, videos, etc...
	
+ libs        

	External libraries

+ private     
	
	Unreadable directory from the web; useful for security logs
	
+ paferalib   

	Main library directory

### Application Structure

Each application can contain the following directories

+ admin        

	Administration pages for the app
	
+ api          

	APIs accessible via JSON
	
+ css          

	Stylesheets
	
+ js           

	JavaScript files
	
+ models       

	Database models
	
+ pages        

	Webpages
	
+ plugins      

	Plugins to be run after the page content has been generated
	
+ translations 
	
	Translations in JSON format

Due to the rewrite rules in .htaccess, direct file access is limited. This is both for security and for convenience in using short URLs. 

### Short URLs

To shorten URLs and to save typing, resources for each application can be addressed using the following scheme:

+ apps/appname/pages/pagename   -> /appname/pagename
+ apps/appname/css/filename     -> /c/appname/filename
+ data/appname/filename         -> /d/appname/filename
+ apps/appname/images/filename  -> /i/appname/filename
+ apps/appname/js/filename      -> /j/appname/filename
+ apps/appname/sounds/filename  -> /s/appname/filename
+ apps/appname/videos/filename  -> /v/appname/filename

Let's say you created an app called test, your main page would be at apps/test/pages/index.php and the URL would be /test/index or /test for short. I think most people would agree that the short form is much easier to type.

## Editing pages

### Hello, world!

The default application for all root-level URLs is called h, which is short for home. To make our traditional "Hello, world!" page, simply edit apps/h/pages/index.php to read

	<p>Hello, world!</p>

and your website's main page will read "Hello, world!"

### Linking pages

All pages in Paferalib are PHP scripts. Let's say that you wanted an about page, you would create apps/h/pages/about.php. You could then update index.php to link to this page by using

	<p>Hello, world!</p>
	
	<p><a href="/about">About me</a></p>
	
### Using the provided objects

Pages in Pafera are not in the global scope, but are actually inserted inside a function in resolver.php. This means that if you want to use any global variables, you need to write at the top of your page as in a function.

	<?php
	
	global $globalvar;

Another consequence of this scope is that the Resolver will automatically include several useful variables for you as well.

+ $pathargs    

	Any arguments passed via the URL but not by query string
	
+ $D           

	Database
	
+ $R           

	Resolver
	
+ $S           

	Session
	
+ $T           
	
	Translator
	
+ $T_SYSTEM    

	System translations
	
+ $T_MAIN      

	Main translations

#### $pathargs

The $pathargs variable contains any extra parameters to the script passed via the URL.

For example, if you have a script at the URL /view and the URL is /view/0/100/icon, $pathargs would become

	['0', '100', 'icon']
	
This is the equivalent positional parameters of a function

	function View($start, $limit, $style)
	
just using the URL rather than calling a function directly.

#### $D

This is the default database for the site, which is initialized before it comes to your page. Paferalib supports using multiple databases simultaneously, which you may want to do if you're supporting multiple simultaneous users using SQLite to avoid locking down the whole database file down whenever a script wants to insert something. If you're using MySQL or anything else that locks only individual tables, it's unlikely that you'll use anything beyond this variable.

#### $R

The Resolver is the object that you will use to identify which app you're in. It also has convenience functions for images, scripts, and other resources that your app might use.

For example, if your app is called test and you have an image at apps/test/images/logo.png, you can either type

	<img src="/i/test/logo.png" />
	
or you can use

	<?=$R->IMG('logo.png')?>
	
to do the same thing without hardcoding the name of your app into your code?

Why would you not want to hardcode the name of your app into your code? 

Paferalib supports app instances, which are copies of your app using different names implemented by Unix or NTFS soft links. Each app has the same code, but different data. If you want your billing department and your sales department to both have forums, you can use the same code under apps/billingforums and apps/salesforums to achieve this result.

It's also possible to have different code paths depending on the name of your app. For example, our file manager "share" doubles as a TV remote under the name "pitv." The additional features are activated by a code block that reads

	if ($R->currentapp == "pitv")
	{
		...
	}

The Resolver is also useful when including other scripts. Instead of writing

	include('apps/test/libs/lib.php');
	
You can write

	$R->IncludePHP('libs/lib.php');
	
to do the same thing or use

	$R->IncludeDir('libs/lib1');
	
to include an entire directory of PHP files at the same time. 

#### $S

The session object exists to save session data into a database rather than on the filesystem. This allows many web servers to share a database server and thus have user sessions available anywhere. You should probably not worry too much about this unless you're a big company with multiple A records for your domain name.

#### $T

Translations are natively built into Paferalib. On the application side, they're stored in JSON files in the translations directory and loadable by this object. On the database side, they're stored into a JSON field.

Let's say that you have an app called test with a translation file called main. You could then load these translations by calling

	$T_TEST_MAIN	= $T->Load('test/main');
	
	print_r($T_TEST_MAIN);
	
$T_TEST_MAIN would then be an array of strings.

#### $T_SYSTEM

System-wide collection of strings useful on every page. Things like "Go," "Cancel," and "Back" to make your life easier.

#### $T_MAIN

Site-specific collection of strings. This should contain your site name and anything else that's useful on multiple pages.

## Using the database

The database class is... strangely enough... called DB. The default instance can be found in $D. All site settings including database login information can be found in the JSON file private/pafera.cfg. It would be a good idea to ensure that you don't accidentally send or upload this file anywhere.

Paferalib's database supports all of the normal SQL operations and includes object linking, translations, tagging, properties, and many other convenient tools. Of course, the downside to using any type of generic tools is that you trade development time for execution speed, but we like to stick by the old "Make it work first, then optimize later" philosophy.

### Flags

The database class has several flags which you may find useful.

+ DB::DEBUG

	Prints all queries and results.
	
+ DB::SECURE

	Activates ownership, default permissions, and access control lists. Note that both the database and the model must have this flag set in order to be used, so you can have some models which are secure and some models which are not. Once activated, you can set permissions on database rows just like on your filesystem for viewing, changing, creating, and deleting. Note that this comes with a significant performance penalty, so only use it in those cases where you absolutely need it.
	
+ DB::TRACK_CHANGES

	Paferalib includes a changelog which keeps track of every creation, modification, or deletion to the system. Like the DB::SECURE flag, this is set on an individual model basis, and decreases performance. If you want to know that Carl from accounting updated Bob's invoice, this is for you. Just be aware that heavily modified websites can fill up your disk space *very* quickly!
	
+ DB::TRACK_VALUES

	An enhanced version of DB::TRACK_CHANGES, this not only tracks changes but also the values which were changed. This allows you to have a form of version control where any object can be returned to an earlier state at any time, but again, will *rapidly* consume your disk space.
	
+ DB::PRODUCTION

	Signals to the system that this is a production system, which will make the following changes.
	
	+ Disables normal error displays
	+ Use minified versions of all JavaScript and CSS files
	+ Enables enhanced caching for code and data

	This will also make it much harder to debug your code, which is why it can easily be switched on and off as needed.
	
### Models

Paferalib does not hide its SQL backend. In fact, it puts it right in front of you and lets you write your own SQL queries to take advantage of your system.

Database models live in the models directory of your app. They take their name from the filename, are always lowercase, and support autoloading so that you don't have to include a file for every model that you want to use. Instead, the first time you create or search for a model is the time where it will be autoloaded.

A model file looks something like the following:

	file loginattempt.php

	<?php

	class templateclass extends ModelBase
	{
		public static	$DESC		=	[
			'numsuids'	=> 1,
			'flags'			=> 0,
			'uniqueids'		=>	['phonenumber', 'place'],
			'fields'		=>	[
				'phonenumber'		=>	['TEXT NOT NULL'],
				'place'				=>	['TEXT NOT NULL'],
				'timestamp'			=>	['INT32 NOT NULL'],
				'ipaddress'			=>	['INT32 NOT NULL'],
				'flags'				=>	['INT32 NOT NULL'],
			],
			'indexes'				=>	[
				['INDEX', 'ids'],
			],
		];
	}

The special "templateclass" keyword is the name of the model. This will vary depending on the name of the app. If your app is named "test," this model will become "test_loginattempt," and you will create it using the line

	$attempt	= $D->Create('test_loginattempt');
	
For convenience, the keyword "templateapp" will be replaced by the name of the current app. Thus if you have a sibling model called "user," you can load it inside the loginattempt definition using the code 

	$user	= $D->Create('templateapp_user');

The preferred way to use models in Paferalib is to place the database definition and any commonly used functions in the model itself, but any logic that is used only once should be placed within an API script. This keeps down the amount of code which needs to be loaded and parsed every time an object is used.

#### Model definition

The main definition is found in the static variable $DESC. This can have the following members:

+ flags

	The default flags for the model, modifiable by the site administrator at run-time. This includes DB::SECURE, DB::TRACK_CHANGES, and DB::TRACK_VALUES.
	
+ numsuids

	The term "suid" stands for Synchronization Unique ID, and is an implementation of a random ID across the int32 address space for every insertion. In simpler terms, every time you insert an object into the database, it automatically gets an unused ID from -2147483648 to 2147483647 excluding zero. It allows for Bob, Jack, and Mary to all have their own copies of the database, add their own items to it, and then come back and easily merge their changes into the main database, which would be rather inconvenient on a system which used automatically incrementing IDs.
	
	This can take any value above zero, but remember that IDs take up space. 1 is a simple 32-bit value, but 4 takes up 128 bits for every row of your model. Unless you really need to store more than four billion rows, 1 should be enough for everyday use.
	
+ uniqueids

	There are three ways to identify a given row in Paferalib
	
	1. AUTO_INCREMENT ID
	2. SUIDs
	3. Unique IDs
	
	Unique IDs is an array of field names containing what makes this row unique. For example, the loginattempt model can be uniquely identified by the phone number and place of the user trying to login, since different places can have the same phone number.
	
	It is quite possible for a model to have all three ways of identifying, in which case the database will first use the unique IDs, then try to use the SUIDs, then finally use the AUTO_INCREMENT ID.
	
	Note that using unique IDs automatically creates an unique index for the fields in the database as well for efficiency.
	
+ fields

	The fields is the real definition of the SQL CREATE TABLE statement. It contains an array whose keys are the field names and the values are the field definitions. The definitions are an array in the form [type, validator, extra] with only the type required.
	
	Available types are the common SQL types INT, FLOAT, TEXT, BLOB along with some custom Paferalib types:
	
	+ DATETIME
	
		Stored as a text string in ISO 8601 format. No Y2K or 2038 problems here!
		
	+ INT8, INT16, INT32, INT64
	
		Integer types which specify the number of bits. Available on pretty much all databases, unlike the more esoteric INT128 or BCD types.
	
	+ JSON
	
		Stored as a UTF-8 string which is automatically encoded to and from a JSON array.
		
	+ TRANSLATION
	
		Stored as an INT32 index to the real translations table in h_translations. Automatically loaded by the database at run-time to the field name plus a "s." For example, the field "username" would result in the translations array being stored in "usernames."
		
	+ SINGLETEXT, MULTITEXT
	
		Text that should be only one single line long or will take up multiple lines. Used in HTML forms to indicate whether this should be a plain <input type="text"> or a <textarea> tag.
		
	+ PASSWORD
	
		Text that should not be shown outside of the system by any means. The JSON DB API automatically stores such fields as having the value "[hidden]."

	+ PRIVATE
	
		Adding this keyword will ensure that these variables will not be readable by anyone except administrators.
		
	+ PROTECTED
	
		Adding this keyword will ensure that these variables will not be readable by anyone except those who have the DB::VIEW_PROTECTED permission.

+ indexes

	This is an array which allows you to specify individual indexes to be created for the table. It takes the form [indextype, indexfields] where indexfields is a string containing field names separated by commas.
	
	For tables which are frequently queried, indexes can dramatically improve performance, but for tables which are frequently written to or updated, indexes can dramatically lower performance. It's suggested that you test your tables both with indexes and without indexes to find the best fit.
	
### Operations

#### CREATE TABLE

You typically will not need to issue a CREATE TABLE statement yourself unless your table is rather complicated. A model named test_user will result in a table called test_users being automatically created on first use.

#### $D->Create()

To create an object, simply pass its name to the $D->Create() method. The class will be autoloaded, initialized with default values, and returned to you.

	$obj	= $D->Create($model);

You should not create new models using the PHP new keyword unless you manually initialize the fields yourself using $D->ImportFields(). If you have not setup correct validators, it's quite possible for bad data to be written into your database when you try to save your object.

#### SELECT

##### $D->Load(), $D->LoadMany()

Like most database abstraction layers, Paferalib has two ways to get an object: load it by ID or find it by criteria.

Loading an object can be done in many ways depending on the type of ID used.

	# Load by SUID
	$obj	= $D->Load('test_loginattempt', $suid1);
	
	# Load by named array
	$obj	= $D->Load('test_loginattempt', ['suid1' => $suid1]);
	
	# Load by unique IDs
	$obj	= $D->Load(
		'test_loginattempt', 
		[
			'phonenumber' => $phonenumber,
			'place'			=> $place,
		]
	);

All of these will produce a test_loginattempt object stored in $obj, throwing an exception if the current user does not have the view permission or if the object could not be found.

For efficiency, it is also possible to specify which fields to load and to provide an object to load into:

	# Load only phonenumber
	$obj	= $D->Load('test_loginattempt', $suid1, 'phonenumber');

	# Load into existing object
	$D->Load('test_loginattempt', $suid1, '', $obj);

If you have a list of IDs, it is possible to load them all at once

	# Loading from an array of IDs
	$objs	= $D->LoadMany('test_loginattempt', $ids);
	
but this is inefficient. Since Paferalib does not know in advance what type of search to perform for each row, it results in a query for every object. You should perform the load yourself for greatest performance using a WHERE clause. 

##### $D->Find()

Finding an object is probably about 99% of what most people use SQL for, and also can be the most complicated operation to do once you start doing JOINs and subselects and all of those fun features that DBAs specialize at.

Paferalib has portability and ease of use as two of its goals, so we do *not* include any database-specific functions. We use SQLite as a baseline, meaning that if it can be done in SQLite, it can probably be done in every other database as well. For most people, this won't make much of a difference, but if your app has performance-critical parts, you can always use a direct query with $D->Query() to optimize for your particular database. CREATE VIEW and the database's native query cache can also help for frequently used queries.

	# Simplest form returns all rows
	$objs	= $D->Find('test_loginattempt')->All();
	
	# Using a WHERE clause with parameters is the normal use
	$objs	= $D->Find(
		'test_loginattempt', 
		'WHERE phonenumber = ?', 
		$phonenumber
	)->All();

	# ORDER BY can be written straight into the WHERE clause
	$objs	= $D->Find(
		'test_loginattempt', 
		'WHERE phonenumber = ?
		ORDER BY phonenumber', 
		$phonenumber
	)->All();
	
	# but LIMIT should be put into the options array due to chunking
	$objs	= $D->Find(
		'test_loginattempt', 
		'WHERE phonenumber = ?
		ORDER BY phonenumber', 
		$phonenumber,
		[
			'start'	=> 100,
			'limit'	=> 100
		]
	)->All();

###### DBResult

Like regular PDO queries return a SQL cursor, $D->Find() returns a DBResult class. The most commonly used style is to use a foreach loop to iterate over each row that $D->Find() returns, and DBResult is designed to support exactly such a use.

	foreach ($D->Find(
			'test_loginattempt', 
			'WHERE phonenumber = ?
			ORDER BY phonenumber', 
			$phonenumber,
			[
				'start'	=> 100,
				'limit'	=> 100
			]
		) as $r
	)
	{
		print $r->phonenumber;
	}
	
DBResult natively supports chunking, or reading a portion of rows at a time for processing to save memory. This means that if your database returns a million rows, DBResult will first fetch rows 0-999, then rows 1000-1999, and so forth. The chunk size defaults to 1000, and can be set using the 'chunksize' option.

DBResult also supports caching the returned objects to the webserver, thus vastly improving performance for subsequent queries with the same parameters. This can be enabled by setting the 'cachesize' option to the number of rows that you wish to be cached. The next time that you run the same query, DBResult will check to see if the number of rows in the table has changed. If the count is still the same, your objects will be loaded from disk rather than having to make another trip to the database server. 

Like $D->Load(), $D->Find() supports retrieving only specific fields from the database to save processing time. You can set these as a string with each field separated by a comma in the 'fields' option.

It's also possible to retrieve only objects with certain permissions. For example, if you only wish to get objects which you have permission to change, you can set 'access' to DB::CAN_CHANGE and DBResult will return only those objects.

The top mistake to make with DBResult is that it will not return all of the rows of your database by default. Instead, it will only return the first 1000 rows, which saves a lot of processing for most common operations. If you wish to get rows past the first 1000, make sure to set the 'limit' option to a large number.

Because of security, chunking, and the processing limit, getting a precise count from DBResult when you have a secure model is only possible if you retrieve *all* rows and then manually count how many rows you have. For secure models, it's quite possible that certain rows are not viewable by your user and thus will be skipped over. Models without security do not have this issue since all of their rows are public.

##### ModelBase

All Paferalib models must inherit from ModelBase in order to receive variable tracking and validation capabilities. 

###### Method forwarding and chaining

ModelBase keeps track of which database this object came from and will forward most methods to that database. These methods are also normally chainable, so instead of doing

	$obj	= $D->Create('model');
	$obj->Set(['property' => 'foo']);
	$D->Insert($obj);
	
You could write the equivalent as

	$D->Create('model')->Set(['property' => 'foo'])->Insert();

###### Setting properties

It's important that you remember that properties cannot be set directly as in

	$model->property = 'foo';
	
but must be set using the ModelBase->Set() method

	$model->Set(['property' => 'foo']);
	
While this will seem awkward, ModelBase->Set() will keep track of which variables have changed and which variables have not. When it's time to update the object, calling ModelBase->Save() will result in a no-op if nothing has changed.

An additional benefit is that if you have setup your validators correctly, searching for, loading, and updating an object from a POST request can be as simple as

	$D->Create('model')->Set($_REQUEST)->Replace();
	
Most objects will require more processing than this, but for models which do not require advanced validation, this can be very convenient.

###### Useful methods

ModelBase has several useful methods which you might find yourself using from time to time.

+ ModelBase->ToArray()

	Returns all of the properties and values as an array. This method respects properties marked as PRIVATE or PROTECTED.

+ ModelBase->ToJSON()

	Returns all of the properties and values as an array for conversion to and from JSON. 

+ ModelBase->Changed()

	Returns whether this object has been changed.
	
+ ModelBase->OnLoad(), ModelBase->OnSave(), ModelBase->OnDelete(), ModelBase->PostSave()

	Processing hooks called whenever their named events occur for further functionality. These come in handy if you're dealing with hierarchical trees or other special data structures which SQL does not cope with very easily.
	
+ ModelBase->CanChange(), ModelBase->CanDelete()

	Convenience functions for checking what the current user can do.
	
##### $D->Save(), $D->Insert(), $D->Update(), $D->Replace()

One of the main frustrations with SQL is to decide what to do when you're trying to insert a row with the same ID as an existing row. The MySQL REPLACE keyword does a lot to help with this situation, ut unfortunately is not supported by all databases, and thus not includable in Paferalib.

+ $D->Save() 

	This is the backend function to all of the other methods. This should not be called directly unless you're sure of what you're doing.

+ $D->Insert() 

	This will do the same thing as the SQL INSERT INTO clause, and will fail if a row exists with the same ID.

+ $D->Update()

	This will do the same thing as the SQL UPDATE clause, and will do nothing if no row exists with the same ID.

+ $D->Replace()
	
	As a compromise between the above functions, this method will first search for any existing rows, do an update if it finds one, or does an insert if it doesn't find one. The downside is that these searches take time, and this is substantially slower than calling $D->Insert() or $D->Update() yourself if you know for sure what you need to do.
	
With all of these functions, the action of saving an object involves converting all of its properties into formats suitable for the database, calling any necessary validators, and then sending the command to the database itself. All of these can result in exceptions being thrown, so be sure to wrap any save operations in an exception handler.

Any special handling for a model can be done by defining the functions ModelBase->OnSave() for before the save is done and ModelBase->PostSave() for after the save is done. If you want to convert any special types or launch any hooks, this is the place to do it.

##### $D->Delete()

In its simplest form, $D->Delete() will truncate the model's table

	$D->Delete($modelname)
	
It can also delete only specific rows

	$D->Delete($modelname, 'WHERE phonenumber = ?', $phonenumber)

Plain and simple is the description for this method.

##### $D->Link(), $D->Linked(), $D->Unlink(), $D->UnlinkMany()

Paferalib natively supports linking objects to each other in a many-to-many relationship. These links can have a type, an order, and a comment as to what the purpose of the link is. It's not uncommon to see code like

	$bob	= $D->Find('user', 'WHERE username = ?', 'Bob')[0];
	$tom	= $D->Find('user', 'WHERE username = ?', 'Tom')[0];
	$jane	= $D->Find('user', 'WHERE username = ?', 'Jane')[0];
	$mary	= $D->Find('user', 'WHERE username = ?', 'Mary')[0];

	$bob->Link($tom, BOSS);
	$bob->Link([$jane, $mary], EMPLOYEES);
	
	var_dump($bob->Linked('user', BOSS)); # Returns $tom
	var_dump($bob->Linked('user', EMPLOYEES)); # Returns [$jane, $mary] in that order
	
