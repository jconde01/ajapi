<?php

namespace App;

use App\Client;
use App\MovDetail;
use Illuminate\Database\Eloquent\Model;

class MovHeader extends Model
{
    protected $table = 'mov_h';
    protected $primaryKey = 'idmov_h';
    public $timestamps = false;    

    public function detalles() {
    	return $this->hasMany(MovDetail::class,'idmov_h');
    }

    public function cliente() {
    	return $this->belongsTo(Client::class);
    }
}
