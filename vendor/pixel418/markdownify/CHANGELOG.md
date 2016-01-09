CHANGELOG
==============


01/04/2015 v2.1.9
--------------

 * Fix: Handle HTML breaks & spaces in a less destructive way.


26/03/2015 v2.1.8
--------------

 * Fix: Use alternative italic character
 * Fix: Handle HTML breaks inside another tag
 * Fix: Handle HTML spaces around tags


07/11/2014 v2.1.7
--------------

 * Change composer name to "elephant418/markdownify"


14/07/2014 v2.1.6
--------------

 * Fix: Simulate a paragraph for inline text preceding block element
 * Fix: Nested lists
 * Fix: setKeepHTML method
 * Feature: PHP 5.5 & 5.6 support in continuous integration


16/03/2014 v2.1.5
--------------

Add display settings

 * Test: Add tests for footnotes after every paragraph or not
 * Feature: Allow to display link reference in paragraph, without footnotes


27/02/2014 v2.1.4
--------------

Improve how ConverterExtra handle id & class attributes:

 * Feature: Allow id & class attributes on links
 * Feature: Allow class attributes on headings