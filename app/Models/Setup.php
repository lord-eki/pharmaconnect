<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setup extends Model
{
    

protected $fillable = [
        'logo_path',
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
    ];

    


}
