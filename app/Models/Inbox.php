<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbox extends Model
{
    const WAITING = 0;
    const PROGRESS = 1;
    const DONE = 2;
    const PENDING = 3;

    const STATUS = [
        "waiting \xE2\x9A\xA0",
        'progress 🧑‍💻',
        "done \xE2\x9C\x85",
        "pending \xE2\x9C\x96"
    ];
    
    use HasFactory;

    public function scopeWaiting($q)
    {
        return $q->where('status', self::WAITING);
    }

    public function scopeProgress($q)
    {
        return $q->where('status', self::PROGRESS);
    }
}
