# PP: php-preprocessor

A very simple PHP Preprocessor.
Buggy and *not* turing complete.
The syntax is compatible with PHP.
I use it to turn development code into a production installation.
This is simply done by deleting lines from production build.


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
