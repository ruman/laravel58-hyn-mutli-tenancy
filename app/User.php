<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Broadcasting\PrivateChannel;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'first_name', 'last_name', 'timezone', 'photo_path'
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
	* The attributes that should be cast to native types.
	*
	* @var array
	*/
	protected $casts = [
		'email_verified_at' => 'datetime',
	];

	protected $guard_name = 'api';

	public function disable(){
		$this->status = 'disabled';
		return $this->save();
	}

	public function activate(){
		$this->status = 'active';
		return $this->save();
	}

	public function receivesBroadcastNotificationsOn()
	{
		$connection = app(\Hyn\Tenancy\Database\Connection::class);

		$tenantConnection = $connection->get();

		$dbName = $tenantConnection->getDatabaseName();

		$website = app(\Hyn\Tenancy\Environment::class)->tenant();

		$uuid = $website ? $website->uuid : $dbName;

		return 'App.User.'.$uuid.'.'.$this->id;
	}

	public function accessTokens()
	{
		return $this->hasMany(OauthAccessToken::class);
	}

}
