<?php

namespace App\Mail;

use App\Newsletter;
use App\Subscriber;
use App\Theme;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewsletter extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;
    public $subscriber;
    public $theme;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Newsletter $newsletter, Subscriber $subscriber, Theme $theme)
    {
        $this->newsletter = $newsletter;
        $this->subscriber = $subscriber;
        $this->theme = $theme;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('themes.'.$this->theme->slug.'.emails.newsletter');
    }
}
