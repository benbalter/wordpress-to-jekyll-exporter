## Minimum required PHP version

Many shared hosts may use an outdated version of PHP by default. **WordPress to Jekyll Export requires PHP 5.6 or greater.**

If you get an error message that looks like `unexpected T_STRING`, `unexpected '['` or `expecting T_CONSTANT_ENCAPSED_STRING`, you need to update your PHP version. In a shared hosting environment, you should be able to change the version of PHP used by simply toggling the setting in the host's control panel.

PHP 5.4 lost support from the PHP project itself in 2015. You'll need to be running at least PHP 5.5 which adds namespace support (the reason it's breaking), but I'd recommend at least 5.6 (or the latest your host supports) as it's the oldest supported version: <https://en.wikipedia.org/wiki/PHP#Release_history>
