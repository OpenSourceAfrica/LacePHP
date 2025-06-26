<?php

namespace Weave\Controllers;

use Lacebox\Sole\Cobble\QueryBuilder;
use Lacebox\Sole\RequestValidator;
use Weave\Models\User;

class LaceUpController
{
    public function hello()
    {
        // 1) define rules (with comma delimiter)
//        RequestValidator::getInstance()
//            ->setCustomRules([
//                'isEven' => new IsEvenRule()
//            ])
//            ->setRules([
//                'email'    => 'required,email',
//                'password' => 'required,min:8',
//                'age'      => 'required,custom:isEven'
//            ])
//            ->validate();

        RequestValidator::getInstance()
            ->lace_break()            // per‐field bail
            ->setRules([
                'email'    => 'required,email',
                'password' => 'min[8]',
            ])
            ->validate();             // on fail: sends 422+JSON and exit


        // 2) fetch data
        $email = sole_request()->input('email');


//        return [
//            'message' => 'You just laced up lacePHP!',
//            'version' => '1.0.0'
//        ];
    }

    public function html()
    {
        return '<h1>Welcome to lacePHP!</h1>';
    }

    public function error()
    {
        return kickback()->serverError('Something went wrong.');
    }

    public function test()
    {
        // fetch all users
//        $users = User::all();

//        // find one
//        $u = User::find(42);
//        if ($u) {
//            return ['email'=>$u->email];
//        }

        //insert
//        $new = new User(['name'=>'Alice','email'=>'a@b.com']);
//        $new->save();

        return QueryBuilder::table('users')
            ->select(['id','name'])
            ->where('email','LIKE','%@b.com')
            ->get();

    }
}