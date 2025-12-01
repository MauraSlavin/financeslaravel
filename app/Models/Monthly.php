<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monthly extends Model
{
    protected $table = 'monthlies';

    protected $fillable = [
        'name',
        'dateOfMonth',
        'trans_date',
        'account',
        'toFrom',
        'amount',
        'category',
        'bucket',
        'notes',
        'comments',
        'copied'
    ];
}