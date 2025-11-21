<?php

namespace Zazama\DoubleOptIn\Tests\Src;

use Page;
use SilverStripe\Dev\TestOnly;
use Zazama\DoubleOptIn\Controllers\VerificationController;

class VerificationTestPage extends Page implements TestOnly
{

    private static string $table_name = 'VerificationTestPage';

    public function getControllerName()
    {
        return VerificationController::class;
    }
}
