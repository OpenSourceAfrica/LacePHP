<?php

namespace Weave\Controllers;

use Lacebox\Insole\LaceVersion;

class LaceUpController
{
    public function hello()
    {
        return [
            'message' => 'You just laced up lacePHP!',
            'version' => lace_version()
        ];
    }
}