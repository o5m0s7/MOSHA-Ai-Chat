<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
        protected $fillable = [
        'user_id',
        'chat_id',
        'provider_id',
        'parent_message_id',
        'role',
        'status',
        'content',
        'error',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function parent()
    {
        return $this->belongsTo(Message::class,'parent_message_id');
    }

    public function responses()
    {
        return $this->hasMany(Message::class,'parent_message_id');
    }
}
