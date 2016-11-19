<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\SocialUser;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'avatar'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Always load providers
     *
     * @var array
     */
    protected $with = ['providers'];

    /**
     * @return mixed
     */
    public function providers()
    {
        return $this->hasMany(SocialUser::class);
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getProvider($type)
    {
        $provider = $this->providers()->where('provider', $type)->first();
        if ($provider && $provider->token) {
            return $provider;
        }

        return null;
    }

    /**
     * @param $provider
     * @return string|null
     */
    public function getProviderToken($type) {
        $provider = $this->getProvider($type);

        if ($provider) {
            return $provider->token;
        }

        return null;
    }
}
