<?php

use Phalcon\Validation,
    Phalcon\Validation\Validator\Email as EmailValidator,
    Phalcon\Validation\Validator\Regex as RegexValidator,
    Phalcon\Validation\Validator\InclusionIn as InclusionIn,
    Phalcon\Filter as Filter;

class ThapovanValidation extends Validation {

    function __construct() {

    }

    /**
     *
     * @param String $email
     * @return Array (errors) if avaliable, else NULL
     */
    public function validEmail($email) {
        $errors = '';
        $validation = new Phalcon\Validation ();
        $validation->add(FieldConstants::EMAIL, new EmailValidator(array(
            CommonConstants::DI_MESSAGE => "Email is not valid"
        )));

        $messages = $validation->validate(array(
            FieldConstants::EMAIL => $email
        ));
        if (count($messages)) {
            foreach ($messages as $message) {
                $errors .= $message . ',';
            }
        }
        return $errors;
    }

    /**
     *
     * @param String $input
     * @param String $fieldName
     * @return error string / NULL
     */
    public function validInteger($input) {
        $errors = '';
        $validation = new Phalcon\Validation ();
        $validation->add('validInterger', new RegexValidator(array(
            'pattern'                   => '/^[0-9]*$/',
            CommonConstants::DI_MESSAGE => 'Invalid argument'
        )));

        $messages = $validation->validate(array(
            'validInterger' => $input
        ));

        if (count($messages)) {
            foreach ($messages as $message) {
                $errors .= $message . ',';
            }
        }

        return $errors;
    }

    /**
     *
     * @param Integer $phone
     * @param
     *            Boolean chkNullOrEmoty
     * @return String(errors) if present, else NULL
     */
    public function validPhone($phone) {
        if (isset($phone) && strlen($phone) > 0) {
            $errors = '';
            if (strlen($phone) < 10 || strlen($phone) > 10) {
                $errors = "Invalid phone number.";
            }
            if (is_integer(intval($phone)) === false || intval($phone) < 100) {
                $errors = "Invalid phone number.";
            }
            if (empty($errors)) {
                $errors = self::numeric($phone, "phone number");
            }
            return $errors;
        }

        return null;
    }

    /**
     *
     * @param String $input
     * @param String $fieldName
     * @return error string / NULL
     */
    private function numeric($input, $fieldName) {
        $errors = '';
        $validation = new Phalcon\Validation ();
        $validation->add($fieldName, new RegexValidator(array(
            'pattern'                   => '/^[0-9]*$/',
            CommonConstants::DI_MESSAGE => 'Invalid ' . $fieldName
        )));

        $messages = $validation->validate(array(
            $fieldName => $input
        ));

        if (count($messages)) {
            foreach ($messages as $message) {
                $errors .= $message . ',';
            }
        }

        return $errors;
    }

    /**
     *
     * @param String $input
     * @param String $fieldName
     * @return error string / NULL
     */
    public function validString($input, $fieldName) {
        $errors = '';
        $validation = new Phalcon\Validation ();
        $validation->add($fieldName, new RegexValidator(array(
            'pattern'                   => "/([a-z0-9. -']+)/i",
            CommonConstants::DI_MESSAGE => 'Invalid ' . $fieldName
        )));

        $messages = $validation->validate(array(
            $fieldName => $input
        ));

        if (count($messages)) {
            foreach ($messages as $message) {
                $errors .= $message . ',';
            }
        }
        return $errors;
    }

    public function validFloat($value) {
        return (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) ? '' : 'invalid float';
    }

    public function validUrl($url, $fieldName) {
        return (filter_var($url, FILTER_VALIDATE_URL)) ? '' : 'invalid url';
    }

    public function validDate($date, $format = 'Y-m-d') {
        $validDate = DateTime::createFromFormat($format, $date);
        return $validDate && $validDate->format($format) == $date;
    }

    public function validBoolean($fieldName, $fieldValue) {
        $errors = '';
        $validation = new Phalcon\Validation ();
        $validation->add($fieldName, new InclusionIn([
            CommonConstants::DI_MESSAGE => 'Invalid boolean value',
            'domain'                    => [
                True,
                False,
                1,
                0
            ]
        ]));

        $messages = $validation->validate(array(
            $fieldName => $fieldValue
        ));

        if (count($messages)) {
            foreach ($messages as $message) {
                $errors .= $message . ',';
            }
        }
        return $errors;
    }

    private function validJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function sanitizeParams($paramValue, $type = '') {
        $filter = new Filter();
        if (!empty($type)) {
            $paramValue = $filter->sanitize($paramValue, $type);
        } else {
            $paramValue = $filter->sanitize($paramValue, [
                'striptags',
                'trim',
            ]);
        }
        return $paramValue;
    }

    public function validateParams($fieldName = '', $fieldvalue = array(), $redisObj = '', $commonsObj = '') {
        $typeErr = $reqErr = '';
        $paramValue = (isset($fieldvalue [0])) ? $fieldvalue [0] : '';
        $dataType = (isset($fieldvalue [1])) ? $fieldvalue [1] : '';
        $required = (bool) (isset($fieldvalue [2]) && $fieldvalue [2]);
        $default = (isset($fieldvalue [3])) ? $fieldvalue [3] : '';
        $redisKey = (isset($fieldvalue [4])) ? $fieldvalue [4] : '';
        switch ($dataType) {
            case CommonConstants::COM_VALIDATE_USER_ID :
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid user credentials';
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && empty($typeErr)) {
                    $userId = $redisObj->getUserId($paramValue);
                    if ($userId > 0) {
                        //$paramValue = $userId;
                        $paramValue = $this->sanitizeParams($userId, 'int');
                    } else {
                        $typeErr = 'Error - Invalid user credentials';
                    }
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validInteger($paramValue))) {
                    $typeErr = 'Error - Invalid user credentials';
                }
                break;
            case CommonConstants::COM_VALIDATE_INT :
                $paramValue = $this->sanitizeParams($paramValue, 'int');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for integer field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validInteger($paramValue))) {
                    $typeErr = 'Error - Invalid input for integer field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_FLOAT :
                $paramValue = $this->sanitizeParams($paramValue, 'float');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for integer field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validFloat($paramValue))) {
                    $typeErr = 'Error - Invalid input for integer field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_STRING :
                $paramValue = $this->sanitizeParams($paramValue, 'string');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for string field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) { // if it is not a required field, assigning default value
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validString($paramValue, $fieldName))) {
                    $typeErr = 'Error - Invalid input for string field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_EMAIL :
                $paramValue = $this->sanitizeParams($paramValue, 'email');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for email field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validEmail($paramValue))) {
                    $typeErr = 'Error - Invalid input for email field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_PHONE :
                $paramValue = $this->sanitizeParams($paramValue, 'int');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for phone field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validPhone($paramValue))) {
                    $typeErr = 'Error - Invalid input for phone field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_URL :
                $paramValue = $this->sanitizeParams($paramValue, 'string');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for url field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !empty($this->validUrl($paramValue, $fieldName))) {
                    $typeErr = 'Error - Invalid input for url field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_DATE :
                $paramValue = $this->sanitizeParams($paramValue, 'string');
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for date field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !$this->validDate(date('Y-m-d', strtotime($paramValue)))) {
                    $typeErr = 'Error - Invalid input for date field - ' . $fieldName;
                }
                $age = $commonsObj->ageCalculator($paramValue);
                if (empty($reqErr) && empty($typeErr) && !empty($paramValue) && $fieldName == FieldConstants::DOB && ($age <= 0 || $age > 120)) {
                    $typeErr = 'Error - Invalid input for date field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_BOOLEAN :
                $paramValue = (bool) ($paramValue == 1);
                if (!empty($this->validBoolean($fieldName, $paramValue))) {
                    $typeErr = 'Error - Invalid input for boolean field - ' . $fieldName;
                }
                break;
            case CommonConstants::COM_VALIDATE_ARRAY :
                if ($required && (!is_array($paramValue) || count($paramValue) == 0)) {
                    $reqErr = 'Error - Invalid input for array field - ' . $fieldName;
                } elseif (!isset($paramValue) || !is_array($paramValue) || count($paramValue) == 0) {
                    $paramValue = $default;
                }
                break;
            case CommonConstants::COM_VALIDATE_GENDER:
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for string field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) { // if it is not a required field, assigning default value
                    $paramValue = strtoupper(substr($default, 0, 1));
                } else {
                    $paramValue = strtoupper(substr($paramValue, 0, 1));
                }
                break;
            case CommonConstants::COM_VALIDATE_JSON:
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for url field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                if (empty($reqErr) && !empty($paramValue) && !$this->validJson($paramValue)) {
                    $typeErr = 'Error - Invalid input for url field - ' . $fieldName;
                }
                break;
            default :
                if ($required && empty($paramValue)) {
                    $reqErr = 'Error - Invalid input for field - ' . $fieldName;
                } elseif (!isset($paramValue) || empty($paramValue)) {
                    $paramValue = $default;
                }
                break;
        }
        if ($paramValue > 0 && empty($reqErr) && empty($typeErr) && $redisKey == CommonConstants::PROVIDER_OBJ_KEY) {
            $providers = $redisObj->getProviderObj($paramValue);
            if (count($providers) > 0) {
                // do nothing
            } else {
                $typeErr = 'Error - Invalid input for field - ' . $fieldName;
            }
        } elseif ($paramValue > 0 && empty($reqErr) && empty($typeErr) && $redisKey == CommonConstants::PAYER_OBJ_KEY) {
            $payers = $redisObj->getPayerObj($paramValue);
            if (count($payers) > 0) {
                // do nothing
            } else {
                $typeErr = 'Error - Invalid input for field - ' . $fieldName;
            }
        } elseif (empty($reqErr) && empty($typeErr) && $redisKey == CommonConstants::CLAIM_STATUS_OBJ_KEY) {
            $claimStatusCode = $redisObj->getClaimStatusObj(strtolower($paramValue));
            if ($claimStatusCode !== false) {
                // do nothing
            } else {
                $typeErr = 'Error - Invalid input for field - ' . $fieldName;
            }
        }
        $paramValue = (!$paramValue) ? $default : $paramValue;
        $paramValue = $this->sanitizeParams($paramValue);
        return array(
            $paramValue,
            $reqErr,
            $typeErr
        );
    }

    public function getRequiredErrorMessage($fieldName, $commonObj, $errType = 'required') {
        $appendRequired = $appendInvalid = 1;
        switch ($fieldName) {
            case FieldConstants::USER_ID:
            case FieldConstants::EXTERNAL_REF_ID:
                $errStr = "Your Member ID";
                break;
            case FieldConstants::PHONE:
                $errStr = "Mobile Number";
                if ($errType == 'invalid') {
                    $errStr = CommonConstants::INVALID_PHONE;
                    $appendInvalid = 0;
                }
                break;
            case FieldConstants::ADDRESS:
                $errStr = "Street Address";
                break;
            case FieldConstants::ADDRESS_EXTN:
                $errStr = "Address Extn";
                break;
            case FieldConstants::STRIPE_TOKEN:
                $errStr = "Stripe Payment Token";
                break;
            case FieldConstants::MODE_ID:
                $errStr = "Invitation Mode";
                break;
            case FieldConstants::REFERRAL_TOKEN:
                $errStr = "Referral Token";
                break;
            case FieldConstants::DEVICE_ID:
                $errStr = "Device Id";
                break;
            case FieldConstants::PLATFORM:
                $errStr = "Platform";
                break;
            case FieldConstants::INVITED_CODE:
                $errStr = "Invited Code";
                break;
            case FieldConstants::STATE:
                $errStr = "State abbreviation";
                break;
            case FieldConstants::DOB:
                $errStr = "Birth Date";
                break;
            case FieldConstants::ZIPCODE:
                $errStr = 'Zip Code';
                if ($errType == 'invalid') {
                    $errStr = CommonConstants::INVALID_ZIPCODE;
                    $appendInvalid = 0;
                }
                break;
            case FieldConstants:: FIRST_NAME:
            case FieldConstants:: LAST_NAME:
            case FieldConstants::EMAIL:
            case FieldConstants::GENDER:
            case FieldConstants::CITY:
            default:
                $errStr = ucwords($commonObj->fromCamelCase($fieldName));
                break;
        }
        $prefix = $suffix = '';
        if ($errType == 'required' && $appendRequired == 1) {
            $suffix = ' is required';
        } elseif ($errType == 'invalid' && $appendInvalid == 1) {
            $suffix = ' is invalid';
        }

        return $prefix . $errStr . $suffix;
    }

}
