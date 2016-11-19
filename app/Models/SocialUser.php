<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class SocialUser extends Model
{
    protected $primaryKey = 'provider_user_id';

    protected $fillable = ['user_id', 'provider_user_id', 'provider', 'token', 'refresh_token', 'expiration'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
