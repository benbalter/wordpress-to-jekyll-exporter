<?php

/* This file is part of the Markdownify project, which is under LGPL license */

namespace Test\Markdownify;

use Markdownify\Converter;

require_once(__DIR__ . '/../../../vendor/autoload.php');

class ConverterTest extends ConverterTestCase
{


    /* UTILS
     *************************************************************************/
    public function setUp()
    {
        $this->converter = new Converter;
    }

}