<?php

namespace Homeful\Mortgage\Events;

use Homeful\Mortgage\Mortgage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
