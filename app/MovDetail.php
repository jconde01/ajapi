<?php

namespace App;

use App\MovHeader;
use Illuminate\Database\Eloquent\Model;

class MovDetail extends Model
{
	protected $table = 'mov_d';
    protected $primaryKey = 'idmov_d';
    public $timestamps = false;

    public function header() {
    	return $this->belongsTo(MovHeader::class,'idmov_h');
    }    	
}
