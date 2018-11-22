<?php

namespace App;

use App\Client;
use Illuminate\Database\Eloquent\Model;

class CliCon extends Model
{
	protected $table = 'clicon';
    public $timestamps = false;
    public $primaryKey  = 'IDclicon';

    public function cliente() {
    	return $this->belongsTo(Client::class);
    }
}
