## Custom fields

When using custom fields (e.g. with the Advanced Custom fields plugin) you might have to register a filter to convert array style configs to plain values.

By default, the plugin saves custom fields in an array structure that is exported as: 

```php
["my-bool"]=>
    array(1) {
        [0] => string(1) "1"
    }
["location"]=>
    array(1) {
        [0] => string(88) "My address"
    }
```

And this leads to a YAML structure like:

```yaml
my-bool:
- "1"
location:
- 'My address'
```

This is likely not the structure you expect or want to work with. You can convert it using a filter:

```php
add_filter( 'jekyll_export_meta', function($meta) {
    foreach ($meta as $key => $value) {
        if (is_array($value) && count($value) === 1 && array_key_exists(0, $value)) {
            $meta[$key] = $value[0];
        }
    }

    return $meta;
});
```
