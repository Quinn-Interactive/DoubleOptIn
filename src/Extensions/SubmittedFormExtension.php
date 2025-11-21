<?php

namespace Zazama\DoubleOptIn\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use Zazama\DoubleOptIn\Models\EmailVerification;

/**
 * @property int $EmailVerificationID
 * @method EmailVerification EmailVerification()
 * @extends Extension<(SubmittedForm & static)>
 */
class SubmittedFormExtension extends Extension
{
    /**
     * @var array<string, class-string<EmailVerification>>
     */
    private static array $has_one = [
        'EmailVerification' => EmailVerification::class
    ];

    public function canView($member = null)
    {
        if ($this->getOwner()->Parent()) {
            if ($this->getOwner()->Parent()->EnableDoubleOptIn) {
                if ($this->getOwner()->EmailVerification()->Verified || !$this->getOwner()->Parent()->DoubleOptInFieldID) {
                    return $this->getOwner()->Parent()->canView($member);
                } else {
                    return false;
                }
            } else {
                return $this->getOwner()->Parent()->canView($member);
            }
        }
        return false;
    }
}
