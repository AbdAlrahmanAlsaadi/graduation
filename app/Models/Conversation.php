<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['title'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getFirstQuestionAttribute()
    {
        $first = $this->messages()->orderBy('id', 'asc')->first();
        return $first ? $first->content : 'بدون عنوان';
    }
}
