# Source: https://developer.wordpress.org/plugins/hooks/filters/


Filters are one of the two types of Hooks . 

They provide a way for functions to modify data during the execution of WordPress Core, plugins, and themes. They are the counterpart to Actions . 

Unlike Actions , filters are meant to work in an isolated manner, and should never have side effects such as affecting global variables and output. Filters expect to have something returned back to them. 

## Add Filter 

The process of adding a filter includes two steps. 

First, you need to create a Callback function which will be called when the filter is run. Second, you need to add your Callback function to a hook which will perform the calling of the function. 

You will use the add_filter() function, passing at least two parameters: 

- `string $hook_name `which is the name of the filter you’re hooking to, and 

- `callable $callback `the name of your callback function. 

The example below will run when the `the_title `filter is executed. 

```php
function wporg_filter_title( $title ) {
	return 'The ' . $title . ' was filtered';
}
add_filter( 'the_title', 'wporg_filter_title' );
```

Lets say we have a post title, “Learning WordPress”, the above example will modify it to be “The Learning WordPress was filtered”. 

You can refer to the Hooks chapter for a list of available hooks. 

As you gain more experience, looking through WordPress Core source code will allow you to find the most appropriate hook. 

### Additional Parameters 

add_filter() can accept two additional parameters, `int $priority `for the priority given to the callback function, and `int $accepted_args `for the number of arguments that will be passed to the callback function. 

For detailed explanation of these parameters please read the article on Actions . 

### Example 

To add a CSS class to the `<body> `tag when a certain condition is met: 

```php
function wporg_css_body_class( $classes ) {
	if ( ! is_admin() ) {
		$classes[] = 'wporg-is-awesome';
	}
	return $classes;
}
add_filter( 'body_class', 'wporg_css_body_class' );
```
