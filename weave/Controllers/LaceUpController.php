<?php

namespace Weave\Controllers;

use Weave\Models\User;

class LaceUpController
{
    public function hello()
    {
        return [
            'message' => 'You just laced up lacePHP!',
            'version' => '1.0.0'
        ];
    }
}