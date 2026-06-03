<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Catalog enums (see App\Enums\Season, Department, Gender)
 | --------------------------------------------------------------------------
 */
defined('SEASON_SPRING')     || define('SEASON_SPRING', 'spring');
defined('SEASON_SUMMER')     || define('SEASON_SUMMER', 'summer');
defined('SEASON_FALL')       || define('SEASON_FALL', 'fall');
defined('SEASON_WINTER')     || define('SEASON_WINTER', 'winter');
defined('SEASON_ALL_SEASON') || define('SEASON_ALL_SEASON', 'all_season');

defined('DEPARTMENT_APPAREL')      || define('DEPARTMENT_APPAREL', 'apparel');
defined('DEPARTMENT_FOOTWEAR')     || define('DEPARTMENT_FOOTWEAR', 'footwear');
defined('DEPARTMENT_ACCESSORIES')  || define('DEPARTMENT_ACCESSORIES', 'accessories');
defined('DEPARTMENT_ELECTRONICS')  || define('DEPARTMENT_ELECTRONICS', 'electronics');
defined('DEPARTMENT_HOME')         || define('DEPARTMENT_HOME', 'home');
defined('DEPARTMENT_OTHER')        || define('DEPARTMENT_OTHER', 'other');

defined('GENDER_MEN')    || define('GENDER_MEN', 'men');
defined('GENDER_WOMEN')  || define('GENDER_WOMEN', 'women');
defined('GENDER_UNISEX') || define('GENDER_UNISEX', 'unisex');
defined('GENDER_BOYS')   || define('GENDER_BOYS', 'boys');
defined('GENDER_GIRLS')  || define('GENDER_GIRLS', 'girls');
defined('GENDER_KIDS')   || define('GENDER_KIDS', 'kids');

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code
