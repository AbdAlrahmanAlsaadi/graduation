<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    // تأكد من أن اسم العمود في جدول messages هو conversation_id (بدون s)
    protected $fillable = ['conversation_id', 'content'];

    public function conversation()
    {
        // بما أن العمود اسمه conversation_id، لا حاجة لتحديده (Laravel يعرف تلقائياً)
        return $this->belongsTo(Conversation::class);
    }

    public function getRoleAttribute()
    {
        // استخدم $this->conversation (مفرد) وليس $this->conversations (جمع)
        $index = $this->conversation->messages()
            ->where('id', '<=', $this->id)
            ->count() - 1;
        return ($index % 2 == 0) ? 'user' : 'model'; // استخدم model بدلاً من assistant
    }
}
