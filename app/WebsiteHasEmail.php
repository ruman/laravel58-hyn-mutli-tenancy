<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesSystemConnection;

class WebsiteHasEmail extends Model
{
	use UsesSystemConnection;
	protected $table = 'website_has_emails';
    protected $fillable = ['website_id', 'email'];
    protected $primaryKey = ['email', 'website_id'];
	public $incrementing = false;
}
