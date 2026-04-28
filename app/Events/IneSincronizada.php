<?php

namespace App\Events;

use App\Models\IneRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IneSincronizada
{
    use Dispatchable, SerializesModels;

    public $ineRecord;

    public function __construct(IneRecord $ineRecord)
    {
        $this->ineRecord = $ineRecord;
    }
}
