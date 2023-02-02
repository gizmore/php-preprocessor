# PP: php-preprocessor

A very simple PHP Preprocessor.
Buggy and *not* turing complete.
The syntax is compatible with PHP.
I use it to turn development code into a production installation.


## php-preprocessor: Syntax

PP commands start with a `#PP#`.

The only supported command atm is `#PP#delete`.

    $line_to_be_deleted; #PP#delete

## php-preprocessor: Binaries

use composer to install globally.
Then the `pp` command should be available.

    pp file1... file2....
    

### php-preprocessor: License

[MIT](./LICENSE)
