<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkspaceInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $workspaceName,
        public string $inviterName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->workspaceName} on TaskFlow",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.invite',
            with: [
                'workspaceName' => $this->workspaceName,
                'inviterName' => $this->inviterName,
                'token' => $this->invitation->token,
                'acceptUrl' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/')
                    . '/accept-invite?token=' . $this->invitation->token,
            ],
        );
    }
}
