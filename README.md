# PP: php-preprocessor

A very simple PHP preprocessor.
Buggy and *not* turing complete.
The syntax is compatible with PHP, abusing comments.
I use this to turn my code into production mode after updates.
This is done by deleting lines, mostly performance counters like instance, destructor and wakeup counts,
timings and [other metrics](https://github.com/gizmore/phpgdo/blob/main/GDO/Perf/GDT_PerfBar.php#L35).
But also some ifs for stuff you never or always want in production environments.

Enjoy!
 - gizmore

## php-preprocessor: Syntax

PP commands start with a `#PP#`.
`#PP#command#`.

There are 3 supported commands.

    #PP#delete# to delete the current line on production systems.
    #PP#start# to begin a block deletion on production systems.
    #PP#end# to end the current block deletion on production systems.


## php-preprocessor: Binaries

use composer to install globally.
Then the `pp` command should be available. (untested)

    pp file # or use stdin/out
    

### php-preprocessor: License

[MIT](./LICENSE)
