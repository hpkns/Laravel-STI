<?php

namespace Tests\Fakes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}