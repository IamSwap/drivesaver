<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class Progress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $progress;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($progress)
    {
        $this->progress = $progress;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('progress.' . $this->progress['file_id'] . '.' . $this->progress['user_id']);
    }
}
