<?php

namespace App\Contracts\Messaging;

use App\Models\OutboundMessage;

interface MessagingProvider
{
    public function send(OutboundMessage $message): string;

    public function health(): array;
}
