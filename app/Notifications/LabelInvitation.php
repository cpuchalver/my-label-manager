<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class LabelInvitation extends Notification
{
    public function __construct(
        public Invitation $invitation,
        #[SensitiveParameter] public string $token,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $labelName = $this->invitation->label->name;

        return (new MailMessage)
            ->subject(__('You are invited to join :label', ['label' => $labelName]))
            ->line(__(':inviter invites you to join :label as :role.', [
                'inviter' => $this->invitation->invitedBy?->name ?? __('A member'),
                'label' => $labelName,
                'role' => $this->invitation->role->getLabel(),
            ]))
            ->action(__('Accept the invitation'), $this->acceptUrl())
            ->line(__('This link can be used once and expires in :days days.', ['days' => Invitation::ValidityInDays]));
    }

    public function acceptUrl(): string
    {
        return route('filament.app.invitations.accept', ['token' => $this->token]);
    }
}
