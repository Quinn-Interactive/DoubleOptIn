<?php

namespace Zazama\DoubleOptIn\Models;

use SilverStripe\Control\Email\Email;

class EmailDummy extends Email
{
    public function send(): void
    {
    }

    public function sendPlain(): void
    {
    }
}
