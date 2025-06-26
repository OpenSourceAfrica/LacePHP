<?php
namespace Weave\Models;

use Lacebox\Sole\Cobble\Model;

class User extends Model
{
    // optionally override table:
    // protected static $table = 'members';
    protected $fillable = ['name','email'];
}