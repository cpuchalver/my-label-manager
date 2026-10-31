<?php

namespace App\Events;

use App\Models\Label;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the open pages of a label that its data changed. It carries no data, so pages reload what they show.
 */
class LabelActivityUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Label $label) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("labels.{$this->label->getKey()}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'label.activity-updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'labelId' => $this->label->getKey(),
        ];
    }
}
