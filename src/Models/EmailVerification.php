<?php

namespace Zazama\DoubleOptIn\Models;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use Zazama\DoubleOptIn\Services\EmailSender;

/**
 * @property ?string $Email
 * @property ?string $Token
 * @property ?string $DBStorage
 * @property bool $Verified
 * @property int $SubmittedFormID
 * @method SubmittedForm SubmittedForm()
 */
class EmailVerification extends DataObject
{

    use Configurable;

    /**
     * @var array<string, string>
     */
    private static array $db = [
        'Email'     => 'Varchar(255)',
        'Token'     => 'Varchar(255)',
        'DBStorage' => 'Text',
        'Verified'  => 'Boolean(0)'
    ];
    /**
     * @config
     */
    private static string $email_template = 'Zazama\\DoubleOptIn\\Email\\Email';

    /**
     * @var array<string, class-string<SubmittedForm>>
     */
    private static array $has_one = [
        'SubmittedForm' => SubmittedForm::class
    ];
    private static string $subject = 'Email verification';

    private static string $table_name = 'EmailVerification';
    private static string $url_segment = 'verify';

    public function generateToken(): string
    {
        $generator = RandomGenerator::create();
        $token = $generator->randomToken('sha512');
        $this->extend('updateGenerateToken', $token);
        return $token;
    }

    public function getStorage()
    {
        if ($this->DBStorage) {
            return json_decode($this->DBStorage, true);
        } else {
            return null;
        }
    }

    public function getSubject()
    {
        $subject = $this->config()->get('subject');
        $this->extend('updateSubject', $subject);
        return $subject;
    }

    public function init($email, $data = null): static
    {
        $this->Email = $email;
        $this->Token = $this->generateToken();
        if ($data) {
            $this->setStorage($data);
        }
        $this->write();
        $this->extend('updateInit', $this);
        return $this;
    }

    public function Link(): string
    {
        $link = sprintf(
            '%s/%s?token=%s',
            Director::absoluteBaseURL(),
            $this->config()->get('url_segment'),
            $this->Token
        );
        $this->extend('updateLink', $link);
        return $link;
    }

    public function send($subject = null): bool
    {
        if (!$subject) {
            $subject = $this->getSubject();
        }
        $data = ArrayData::create([
            'Link'    => $this->Link(),
            'Token'   => $this->Token,
            'Storage' => $this->getStorage()
        ]);
        return EmailSender::send($this->Email, $subject, $data->renderWith($this->config()->get('email_template')));
    }

    public function setStorage($data): bool
    {
        if ($data) {
            $this->DBStorage = json_encode($data);
            return true;
        } else {
            return false;
        }
    }

    public static function IsAlreadyVerified($token): bool
    {
        return EmailVerification::TokenType($token) === "AlreadyVerified";
    }

    public static function IsBadToken($token): bool
    {
        return EmailVerification::TokenType($token) === "BadToken";
    }

    public static function IsSuccess($token): bool
    {
        return EmailVerification::TokenType($token) === "Success";
    }

    public static function TokenType($token): string
    {
        if (!$token) {
            return "BadToken";
        }
        $emailVerification = EmailVerification::get()->filter('Token', $token)->limit(1)[0];
        if (!$emailVerification) {
            return "BadToken";
        } elseif ($emailVerification->Verified) {
            return "AlreadyVerified";
        } else {
            return "Success";
        }
    }
}
