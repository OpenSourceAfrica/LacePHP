<?php

namespace Weave\Controllers;

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