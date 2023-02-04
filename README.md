# PP: php-preprocessor

A very simple but fast PHP preprocessor.
Buggy and **not** turing complete.

I use this to turn my code into production version, every time after an update in our CI.

This is done by filtering some lines;
performance counters, counting instances, con/destructor stuff, wakeup counters, timings and [other metrics](https://github.com/gizmore/phpgdo/blob/main/GDO/Perf/GDT_PerfBar.php#L35).

But also some `ifs` for stuff you never or always want in production environments, or are OS relevant.

It turned out to be easy and can save quite some clock cycles!

Enjoy,
gizmore

---

## php-preprocessor: Documentation

PP is processing text files line by line,
reading kind of annotations by abusing PHP `#` comments.

It basically looks for the Sequence `#PP#`, case-sensitive, and checks if it is in an actual `'<?php'` place.


### php-preprocessor: Syntax

PP commands start with a `#PP#` and are finished by another `#`.

    #PP#command,args# args do not work yet as not neeed yet.


### php-preprocessor: Commands

There are currently 5 commands:

 - #PP#**delete**# to *delete* the line.
 - #PP#**start**# to begin a *delete* block.
 - #PP#**end**# to end end a *delete* block.
 - #PP#**linux**# to *keep* this line on linux machines.
 - #PP#**windows**# to *keep* this line on windows machines.


## php-preprocessor: Binaries

Maybe you want to use PP as a standalone executable instead of using the lib's API:

1. Use composer to install globally.
2. Then the `pp` command should be available. (untested!)


    pp file # or use stdin/out
    
    
It is not 100% Unix style, but should work on windows and linux.


### php-preprocessor: Options

You can control options via the pp executable or by using the API equivalents.

For API: Use `$pp->option` to get and `$pp->option($value)` to set.

Control your infile source with the parameter or use STDIN.

 - --help: print usage line and exit.
 
 - --outfile path: redirect STDOUT to another file.
 
 - --replace: write output to source file, when not in STDIN.
 
 - --verbose: print verbose processing info to STDOUT.
 
 - --recursive: process all files recursively for the input if it's a folder.
 
 - --simulate: do redirect --outfile to STDOUT. Do **not** write any physical files.
 
 - --phpmode: set the initial *inside* `<?php` state to true. Default is false.
 

### php-preprocessor: Example Usage

1) My [phpgdo](https://github.com/gizmore/phpgdo)
 [autoloader](https://github.com/gizmore/phpgdo/blob/main/GDO7.php#L29)

```
    spl_autoload_register(function(string $name) : void
    {
        if ( ($name[0]==='G') && ($name[3]==='\\') ) # 1 line if
        {   # 2 lines path
            $name = GDO_PATH . str_replace('\\', '/', $name) . '.php'; #PP#windows# This line is only kept on windows machines.
            require $name;
            # 2 lines perf, but removed by #PP# PreProcessor
            global $GDT_LOADED; # #PP#delete#
            $GDT_LOADED++; # #PP#delete#
        }
    });
```


Note that we got rid of 3 costy lines in a **very** hot spot,
but *only* ony windows machines. Like on all my prod servers =)


1. The string replace is only need on windows machines, which i do not recommend for an httpd production-env server.

2. 2 lines performance countings could be deleted for prod machines. This does not seem much, but the function is not using  globals anymore, which *might* be a good hint for the optimizer.

**Update**: *LOL*, The first `str_replace` is always needed,
because `$name` is a classname, using `\\` to seperate namespaces,
but there are other occassions were we can have a performance boost via `#PP#windows#`.


### php-preprocessor: Problems

PP is new, so handle it with care. I also found the first problems:

1. Some of your **line numbers** on prod exception traces are a bit off.

2. The too simple syntax can lead to forced invalid PHP code,
which simply makes some usecase ugly or **not possible** at the moment.


### php-preprocessor: ToDO / Ideas

 - `#PP#if(n)def,varname#` - to **keep** the line only if a constant named varname is / is not defined.

 - `#PP#if(n)is,varname,value#` - to **keep** the line if a constant named "varname" equals "value".

 - --profiler: Replace **all** `if()` calls with calls to a branch counter for a new performance metric.


### php-preprocessor: Contact

Of course your pull requests and / or issues are welcome.

if you want to get in touch, send me an email to gizmore@wechall.net, or visit the
[WeChall](https://www.wechall.net) website.


### php-preprocessor: Dependencies

PP has no third party dependencies, but uses my
[php-filewalker](https://github.com/gizmore/php-filewalker) to traverse directories. 
Filewalker is dependency free.


### php-preprocessor: License

I decided to use the [MIT license](./LICENSE).
