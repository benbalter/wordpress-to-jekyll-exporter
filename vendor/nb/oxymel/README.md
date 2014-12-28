oxymel – a sweet XML builder [![Build Status](https://travis-ci.org/nb/oxymel.png)](https://travis-ci.org/nb/oxymel)
============================

```php
$oxymel = new Oxymel;
echo $oxymel
  ->xml
  ->html->contains
    ->head->contains
      ->meta(array('charset' => 'utf-8'))
      ->title("How to seduce dragons")
    ->end
    ->body(array('class' => 'story'))->contains
      ->h1('How to seduce dragons', array('id' => 'begin'))
      ->h2('The fire manual')
      ->p('Once upon a time in a distant land there was an dragon.')
      ->p('In another very distant land')->contains
        ->text(' there was a very ')->strong('strong')->text(' warrrior')
      ->end
      ->p->contains->cdata('<b>who fought bold dragons</b>')->end
      ->raw('<p>with not fake <b>bold</b> dragons, too</p>')
      ->tag('dragon:identity', array('name' => 'Jake'))
      ->comment('no dragons were harmed during the generation of this XML document')
    ->end
  ->end
  ->to_string();
```

Outputs:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>How to seduce dragons</title>
  </head>
  <body class="story">
    <h1 id="begin">How to seduce dragons</h1>
    <h2>The fire manual</h2>
    <p>Once upon a time in a distant land there was an dragon.</p>
    <p>In another very distant land there was a very <strong>strong</strong> warrrior</p>
    <p><![CDATA[<b>who fought bold dragons</b>]]></p>
    <p>with not fake <b>bold</b> dragons, too</p>
    <dragon:identity name="Jake"/>
    <!--no dragons were harmed during the generation of this XML document-->
  </body>
</html>
```

