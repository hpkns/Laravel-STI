<?php

namespace Tests\Fakes;

use Hpkns\Laravel\Sti\SingleTableInheritance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasFactory;
    use SingleTableInheritance;

    protected array $stiTypeBindings = [
        'post' => Post::class,
        'page' => Page::class,
    ];

    protected $guarded = [];

    protected string $stiAttributeName = 'type';

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}