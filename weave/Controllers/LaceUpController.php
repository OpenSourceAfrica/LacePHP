<?php

namespace Weave\Controllers;

class LaceUpController
{
    public function hello()
    {
        return [
            'message' => 'You just laced up lacePHP!',
            'version' => '2.0.0'
        ];
    }
}