<?php

namespace Homeful\Mortgage\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;
use Homeful\Mortgage\Mortgage;

abstract class MortgageUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Mortgage $mortgage;

    /**
     * Create a new event instance.
     */
    public function __construct(Mortgage $mortgage)
    {
        $this->mortgage = $mortgage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('homeful-channel'),
        ];
    }
}
