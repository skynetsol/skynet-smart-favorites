# Source: https://developer.wordpress.org/plugins/cron/simple-testing/


## WP-CLI 

Cron jobs can be tested using WP-CLI . It offers commands like `wp cron event list `and `wp cron event run {job name} `. Check the documentation for more details. 

## WP-Cron Management Plugins 

Several plugins are available on the WordPress.org Plugin Directory for viewing, editing, and controlling the scheduled cron events and available schedules on your site. 

## _get_cron_array() 

The `_get_cron_array() `function returns an array of all currently scheduled cron events. Use this function if you need to inspect the raw list of events. 

## wp_get_schedules() 

The `wp_get_schedules() `function returns an array of available event recurrence schedules. Use this function if you need to inspect the raw list of available schedules. 
