<?php

namespace Tests\Fakes;

use Hpkns\Laravel\Sti\SingleTableInheritance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class User extends Model
{
    use HasFactory;
    use SingleTableInheritance;


}