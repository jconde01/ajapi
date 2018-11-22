<?php

namespace App;

use App\CliCon;
use App\MovHeader;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
	protected $table = 'cliente';
    public $timestamps = false;
    public $primaryKey  = 'cliente_id';

    public function movtos() {
    	return $this->hasMany(MovHeader::class);
    }

    public function contactos()
    {
    	return $this->hasMany(CliCon::class);
    }
}
