<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    /**
     * Fillable attributes
     *
     * @var array
     */
    protected $fillable = [
        'uuid', 'name', 'url', 'status',
    ];

    /**
     * A file belongs to an user
     *
     * @return void
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
