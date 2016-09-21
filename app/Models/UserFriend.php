<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFriend extends Model
{
    protected $fillable = ['user_id', 'friend_user_id'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function friend()
    {
        return $this->belongsTo(User::class);
    }
}
