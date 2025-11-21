<?php

namespace Zazama\DoubleOptIn\Models;

use SilverStripe\Control\Email\Email;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\DataObject;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;

/**
 * @property ?string $Email
 * @property ?string $Recipient
 * @property ?string $EmailData
 * @property int $SubmittedFormID
 * @method SubmittedForm SubmittedForm()
 */
class UserFormEmailToSend extends DataObject
{
    /**
     * @var array<string, string>
     */
    private static array $db = [
        'Email'     => 'Text',
        'Recipient' => 'Text',
        'EmailData' => 'Text'
    ];

    /**
     * @var array<string, class-string<SubmittedForm>>
     */
    private static array $has_one = [
        'SubmittedForm' => SubmittedForm::class
    ];

    private static string $table_name = 'UserFormEmailToSend';

    public function getData(): array
    {
        $email = unserialize($this->Email ?? '');
        /**
         * Ensure the email's $data field is initialised.
         *
         * If there is no data in the serialised object, the $data field remains
         * uninitialised because the constructor was not called. This makes
         * getData() throw an error, so we catch it and set the data. If
         * getData() succeeds, we don't need or want to set the data. This
         * prevents addData() failing because $data is null.
         *
         * @var Email $email
         */
        try {
            $email->getData();
        } catch (\Throwable) {
            $email->setData(ModelData::create());
        }

        $data = [
            'email' => $email
                ->setHTMLTemplate('email/SubmittedFormEmail')
                ->setPlainTemplate('email/SubmittedFormEmailPlain'),
            'recipient' => unserialize($this->Recipient),
            'emailData' => unserialize($this->EmailData)
        ];
        $data['emailData']['Fields'] = [];
        foreach ($data['emailData']['FieldIDs'] as $fieldid) {
            $data['emailData']['Fields'][] = SubmittedFormField::get()->byID($fieldid);
        }
        foreach ($data['emailData'] as $key => $value) {
            $data['email']->addData($key, $value);
        }
        $data['email']->removeData('Fields');
        $fields = ArrayList::create();
        foreach ($data['emailData']['Fields'] as $field) {
            $fields->add($field);
        }
        $data['email']->addData('Fields', $fields);
        return $data;
    }

    /**
     * @param array<string, mixed> $emailData
     */
    public function setData($email, $recipient, array $emailData): bool
    {
        $emailData['FieldIDs'] = [];
        foreach ($emailData['Fields'] as $field) {
            $emailData['FieldIDs'][] = $field->ID;
        }
        $emailData['Fields'] = null;
        $emailData['Sender'] = null;
        $this->Email = serialize($email);
        $this->Recipient = serialize($recipient);
        $this->EmailData = serialize($emailData);
        return true;
    }
}
