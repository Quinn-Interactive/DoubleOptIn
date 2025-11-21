<?php

namespace Zazama\DoubleOptIn\Controllers;

use Page;
use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Zazama\DoubleOptIn\Models\EmailVerification;
use Zazama\DoubleOptIn\Models\UserFormEmailToSend;

/**
 * @extends \PageController<\Page>
 */
class VerificationController extends PageController
{

    use Configurable;
    /**
     * @config
     */
    private static string $layout_prefix = 'Zazama\\DoubleOptIn\\Verification';
    private static string $template_holder = Page::class;

    public function index(HTTPRequest $request): DBHTMLText
    {
        if ($this->getRequest()->getVar('token')) {
            $token = EmailVerification::get()->filter('Token', $this->getRequest()->getVar('token'))->limit(1)[0];
            if (!$token instanceof EmailVerification) {
                $this->badToken();
                return $this->renderWith([
                    $this->config()->get('layout_prefix') . '_BadToken',
                    Page::class
                ]);
            } elseif ($token instanceof EmailVerification && $token->Verified) {
                $this->alreadyVerified();
                return $this->renderWith([
                    $this->config()->get('layout_prefix') . '_AlreadyVerified',
                    Page::class
                ]);
            } else {
                $token->Verified = true;
                $token->write();
                $this->success($token);
                return $this->renderWith([
                    $this->config()->get('layout_prefix') . '_Success',
                    Page::class
                ]);
            }
        } else {
            $this->badToken();
            return $this->renderWith([
                $this->config()->get('layout_prefix') . '_BadToken',
                Page::class
            ]);
        }
    }

    public function success($token): void
    {
        $emailsToSend = UserFormEmailToSend::get()->where(['SubmittedFormID' => $token->SubmittedFormID]);
        if ($token->SubmittedFormID && $emailsToSend) {
            foreach ($emailsToSend as $emailToSend) {
                $data = $emailToSend->getData();
                $email = $data['email'];
                $recipient = $data['recipient'];
                $emailData = $data['emailData'];

                if ((bool)$recipient->SendPlain) {
                    $body = strip_tags($recipient->getEmailBodyContent()) . "\n";
                    if (isset($emailData['Fields']) && !$emailData['HideFormData']) {
                        foreach ($emailData['Fields'] as $field) {
                            $body .= $field->Title . ': ' . $field->Value . " \n";
                        }
                    }
                    $email->setBody($body);
                    $email->sendPlain();
                } else {
                    $email->send();
                }
                $emailToSend->delete();
            }
        }
        $this->extend('updateSuccess', $token);
    }

    public function badToken(): void
    {
        $this->extend('updateBadToken');
    }

    public function alreadyVerified(): void
    {
        $this->extend('updateAlreadyVerified');
    }
}
