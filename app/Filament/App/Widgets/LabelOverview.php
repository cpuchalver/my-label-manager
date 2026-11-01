<?php

namespace App\Filament\App\Widgets;

use App\Models\Label;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LabelOverview extends StatsOverviewWidget
{
    /**
     * The widget refreshes when the label broadcasts an update, so it does not poll.
     */
    protected ?string $pollingInterval = null;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $label = $this->currentLabel();

        return [
            Stat::make('Members', $label->members()->count()),
            Stat::make('Pending invitations', $label->pendingInvitations()->count()),
        ];
    }

    /** @return array<string, string> */
    protected function getListeners(): array
    {
        return [
            "echo-private:labels.{$this->currentLabel()->getKey()},.label.activity-updated" => '$refresh',
        ];
    }

    protected function currentLabel(): Label
    {
        $label = Filament::getTenant();

        abort_unless($label instanceof Label, 404);

        return $label;
    }
}
