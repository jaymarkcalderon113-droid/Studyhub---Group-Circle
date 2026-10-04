<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** A study subject students can choose (e.g. Math). */
#[Fillable(['name'])]
class Subject extends Model
{
}
