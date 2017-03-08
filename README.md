# php-convict

This is a php implementation of node-convict, with some additional features
and some features yet to be implemented.

## Install
````bash
composer require jenson/convict
````

##TODO

This is far from a completed library yet, even if it works.

The following needs to be done:

* Unit tests
* More formats
* ~~Loading several config files at the same time~~
* More unit tests
* Prettying up the code
* More examples and documentation.
* Implement an optional strict check where errors are thrown if configuration is set that is not defined in the scheme.
* Better exception handling
* Even more unit tests.

## Usage
First define a scheme and then get and set values from
that scheme:

```php
$scheme = '{
  "about": "Special keyword containing a short description of the program",
  "key": {
    "to": {
      "value": {
        "doc": "A value that we can set and get",
        "format": "*",
        "arg": "value",
        "shortarg": "v",
        "env": "PHP_VALUE",
        "default": "foo bar"
      }
    }
  }
}';

$config = new \Convict\Convict($scheme);

$config->validate();

$config->set('key.to.value', 'a value');
$config->get('key.to.value');
$config->get('key');
```

Can be started with

```bash
PHP_VALUE=val php demo.php
php demo.php --value=val
php demo.php --value val
php demo.php -vval
php demo.php --help
```

##API

### Constructor
#### Parameters
**filename | json-string Scheme** The scheme to load, either as a filename or as a JSON string.

**Array Options** Defaults to an empty array. Valid options:

*nohelp => true* Turn of the automatic command line help.

####Throws
Throws an \Exception if the scheme is invalid.

### Convict->get
####Parameters
**string key** Defaults to empty. Use a dot notation to address configuration values.

####Returns
**null** If the key does not exist
**mixed** If the key points to a leaf then that value is returned. If the key points to a part of the configuration tree then that sub-part is returned. An empty key returns the entire config tree.

####Throws
Should not throw an exception.

### Convict->set

####Parameters
**string key** The key to set the value for. Use a dot notation to address configuration values. If the key doesnt exist then it will be created.
**mixed value** The value to set

####Returns
Does not return anything

####Throws
Should not throw an exception.

####Note
The values set are not persisted in any way. Use Convict->writeFile to save them.

Only the leafs can carry values, and if a new leaf is created then the value on the previous position will be discarded. Example:

````PHP
$config->set('a', 'x');
$config->get('a'); // => string 'x'

$config->set('a.b', 'y');
$config->get('a'); // => array ('b' => 'y')

$config->set('a.c', 'z');
$config->get('a'); // => array ('b' => 'y', 'c' => 'z')

````

### Convict->addFormat
Set a custom format for the validator to use. Just use the class name in the format field in the scheme. Case insensitive.

### Parameters
**Convict\Validator\Validator format** An instance of a class implementing the Validator interface.

####Returns
Does not return anything

####Throws
Should not throw an exception.

####The Validator interface
*validate ($key, $value)* Should throw a Validator\ValidationException for invalid values.
*coerce ($value)* Should return a value that has been formated to fit the application.

### Convict->validate

#### Parameters
No parameters

#### Returns
Does not return any value

#### Throws
Will throw either a Validator\ValidationException or an \Exception depending on the circumstances.

#### Note
For convenience sake this function will set the uncaught exception handler function to handle the events it throws out. After the validation it will restore the previous exception handler.

### Convict->loadFile

#### Parameters
**Array | filename files** Will go through all the filenames in the array and load them in order. Also accepts a single file name.

### Returns
Does not return any value.

### Throws
Does not throw any exception.

### Convict->loadConfigJson

#### Parameters
**Array | string jsons** Will go through all the JSON strings and load them to the config as if the were loaded from a file. Can also handle single JSON entries.

#### Returns
Does not return anything

#### Throws
Nope.

##The config file

A config file is loaded with

```php
$config->loadFile('path/to/file');
```
and a file that matches the scheme above can look like this

```json5
/*
* The about block in the scheme will be written here
* as a top comment when generating a config file.
*/
{
  // Can be nested to an arbitrary depth.
  "key": {
    "to": {
      "value": "Foo bar"
    }
  }
}

```

It is not fully json5 compatible, but comments are allowed (and encouraged).

The library can write a config file with

```php
$config->writeConfig('path/to/file');
```
and will use the special about field in the scheme and all the doc fileds as comments.

##Precedence
The rules of precedence are:

1. Command line argument
2. Environment variable
3. Configuration file value
4. Default value
5. Throw an exception because we dont know what to do.


##The scheme
###Format
Validate the value with this format. Does not apply to runtime config::set's.

Supported formats:

* any | *: No validation and no coercion.
* boolean: True-ish or false-ish (includes the string true, yes, 1 etc. Coerces to strict true/false.
* duration: Miliseconds or a duration string such as 5.4d, 3,2h, 100s etc (yes, it takes both . and ,). Coerces to an int representing miliseconds.
* nat: Natural number. No fractions and above zero. Coerces to int.
* path: Not implemented yet. Should verify that it is an existing path.
* Port: Valid port number.
* Size: Either bytes or a size string such as 50m or 2.3G. 

You can add a new format at runtime through config::addFormat(Convict\Validator\Validator).

###Arg
Look for this as a long form command argument. Uses getopt in the background, so add :: afterwards to make it optional.

###Shortarg
Same as arg, but governs the short form (-X etc).

###Env
Load from this environment variable.

###Default
Use this value if nothing else comes along. Note that this will also be subjected to the format rules. This is optional, but if it is omitted and not added at runtime then an exception will be thrown when the config is instanciated.

##Autogenerated help
Per default the config listens for -h and --help and will print a help message and exit. This can be turned of by passing the option "nohelp" => true.

````php
$config = new \Convict\Convict($scheme, [ 'nohelp' => true ]);
````
