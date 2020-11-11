<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class HistoryModel extends Model
{
    protected  $table='history';
    protected $primaryKey='id';
    public $timestamps=false;
}
