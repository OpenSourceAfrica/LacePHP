<?php

namespace Weave\Controllers;

use Lacebox\Sole\Cobble\QueryBuilder;
use Lacebox\Sole\Http\ShoeRequest;
use Lacebox\Sole\RequestValidator;
use Weave\Models\User;

class LaceUpController
{
    public function hello()
    {
        $name = ShoeRequest::grab()->input('name');
        return [
            'message' => 'You just laced up lacePHP!',
            'version' => '1.0.0'
        ];
    }

    public function html()
    {
        return '<h1>Welcome to lacePHP!</h1>';
    }

    public function error()
    {
        return kickback()->serverError('Something went wrong.');
    }
}