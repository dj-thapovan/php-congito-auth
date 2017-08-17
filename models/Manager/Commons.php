<?php

class Commons extends Phalcon\Mvc\Model {

    public $pages = 0;

    /**
     * This function for execute the SQL query
     *
     * @param : $sqlQuery
     *            contains SP names and $paramArray contains param names
     * @throws exception $e Throws sql syntax error
     * @author Malini Chandrasekar
     * @return array $spParamArray DB values
     */
    public function executeQuery($sqlQuery, $paramArray = array(), $paramFetchAll = false) {
        $di = Phalcon\DI::getDefault();
        $logger = $di->get(CommonConstants::DI_LOGGER);
        $logStr = "executeQuery" . $sqlQuery;
        $logStr .= (isset($sqlQuery) && isset($paramArray)) ? ", params " . json_encode($paramArray) : '';
        $logger->log(\Phalcon\Logger::INFO, $logStr);

        $spParamArray = array_values($paramArray);
        if (count($spParamArray) > 0) {
            $bindArr = [];
            foreach ($spParamArray as $val) {
                $bindArr[] = '?';
            }
            $sqlQuery = str_replace('()', '(' . implode(',', $bindArr) . ')', $sqlQuery);
        }
        try {
            $robot = new Commons ();
            if ($paramFetchAll) {
                return $robot->getReadConnection()->fetchAll($sqlQuery, Phalcon\Db::FETCH_ASSOC, $spParamArray);
            } else {
                return $robot->getReadConnection()->fetchOne($sqlQuery, Phalcon\Db::FETCH_ASSOC, $spParamArray);
            }
        } catch (exception $e) {
            echo $e;
            die();
            $logger->log(\Phalcon\Logger::ERROR, __FUNCTION__ . $e->getMessage());
        }
    }

    /**
     * This function for page count
     *
     * @param : $rows
     *            contains no of rows
     * @author Malini Chandrasekar
     * @return no of pages
     */
    public function pageCount($rows, $pageLimit = 10) {
        $reminder = (fmod($rows, $pageLimit) > 0) ? 0.5 : 0;
        $this->pages = (int) round(($rows / $pageLimit) + $reminder);
        return $this->pages;
    }

    public function dollarToCent($amount) {
        $amount = (is_numeric($amount)) ? $amount : 0;
        return (int) ($amount * 100);
    }

    public function centToDollar($amount) {
        $amount = (is_numeric($amount)) ? $amount : 0;
        return (float) ($amount / 100);
    }

    public function ageCalculator($dob) {
        if (!empty($dob)) {
            $birthdate = new DateTime($dob);
            $today = new DateTime('today');
            return $birthdate->diff($today)->y;
        } else {
            return 0;
        }
        //return intval(date('Y', time() - strtotime($dob))) - 1970;
    }

    /**
     * This function is used to create the JWT token
     *
     * @param : $jwt
     *            contains jwt token
     * @author Malini Chandrasekar
     * @return : $token returns encrypted data
     */
    public function createJwtToken($jwt, $data) {
        $key = CommonConstants::JWT_KEY;
        return $jwt->encode($data, $key);
    }

    /**
     * This function is used to decrypt the JWT token
     *
     * @param : $jwt
     *            contains jwt token
     * @author Malini Chandrasekar
     * @return : $decryptedData returns decrypted data
     */
    public function decryptJwtToken($jwt, $data) {
        $key = CommonConstants::JWT_KEY;
        $alg = array(
            'HS256'
        );
        return $jwt->decode($data, $key, $alg);
    }

    /**
     * This function is used to convert param name into camel case
     *
     * @param array $params
     *            contains column name
     * @author Malini Chandrasekar
     * @return : $keyName returns column name in camelcase
     */
    public function columnNameToCamelCase($columnName) {
        // match underscores and the first letter after each of them,
        // replace the matched string with the uppercase version of the letter
        return preg_replace_callback('/_([^_])/', function (array $m) {
            return ucfirst($m [1]);
        }, $columnName);
    }

    /**
     * Converts camelCase string to have spaces between each.
     * @param $camelCaseString
     * @return string
     */
    public function fromCamelCase($camelCaseString) {
        $re = '/(?<=[a-z])(?=[A-Z])/x';
        $a = preg_split($re, $camelCaseString);
        return join($a, " ");
    }

    public function generateRequestInviteCode() {
        $chars = "023456789abcdefghijkmnopqrstuvwxyz";
        $i = 0;
        $str = "";
        while ($i < 10) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
            $i++;
        }
        return $str;
    }

    public function responseContent($responseObj, $app) {
        if (isset($responseObj [CommonConstants::DI_SUCCESS]) && $responseObj [CommonConstants::DI_SUCCESS]) {
            $app->response->setStatusCode(200, CommonConstants::DI_OK)->sendHeaders();
            $result = array(
                CommonConstants::DI_SUCCESS => 1,
                CommonConstants::DI_MESSAGE => $responseObj [CommonConstants::DI_MESSAGE]
            );
        } else {
            $app->response->setStatusCode(400, CommonConstants::DI_BAD_REQUEST)->sendHeaders();
            $result = array(
                CommonConstants::DI_SUCCESS => 0,
                CommonConstants::DI_ERROR   => $responseObj [CommonConstants::DI_MESSAGE]
            );
        }
        return $result;
    }

    public function trimSpace($strValue) {
        return trim($strValue);
    }

    /**
     * This function is used validate the form fields
     *
     * @param
     *            array : $inputParamArr contains form field_name,data_type,value, default value
     * @author Malini Chandrasekar
     * @return array : $result returns validated value
     */
    public function formFieldValidation($inputParamArr = array(), $app = '', $logger = '', $apiErrString = '') {
        $errors = $params = array();
        $redisObj = new RedisCache ();
        $validator = new ThapovanValidation ();
        foreach ($inputParamArr as $fieldName => $fieldValue) {
            if (is_array($fieldValue) && count($fieldValue) > 1) {
                $resultArr = $validator->validateParams($fieldName, $fieldValue, $redisObj, $this);
                if (!empty($resultArr [1])) {
                    $errors ['required'] [] = array(
                        FieldConstants::FIELD       => $fieldName,
                        FieldConstants::DESC        => 'required', //$validator->getRequiredErrorMessage($fieldName, $this),
                        CommonConstants::DI_MESSAGE => $validator->getRequiredErrorMessage($fieldName, $this, 'required')
                    );
                } elseif (!empty($resultArr [2])) {
                    $errors ['dataType'] [] = array(
                        FieldConstants::FIELD       => $fieldName,
                        FieldConstants::DESC        => 'invalid', // . $fieldName, //$validator->getRequiredErrorMessage($fieldName, $this),
                        CommonConstants::DI_MESSAGE => $validator->getRequiredErrorMessage($fieldName, $this, 'invalid')
                    );
                } else {
                    $params [$fieldName] = $resultArr [0];
                }
            } else {
                $params [$fieldName] = $fieldValue [0];
            }
        }
        if (isset($errors ['required']) && is_array($errors ['required']) && count($errors ['required']) > 0) {
            $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
            $result = array(
                CommonConstants::DI_ERROR      => CommonConstants::MISSING_REQUIRED_PARAMETERS,
                CommonConstants::DI_PARAMETERS => $errors ['required']
            );
            $logger->log(\Phalcon\Logger::ERROR, $apiErrString . " - " . CommonConstants::MISSING_REQUIRED_PARAMETERS . " - " . json_encode($result));
        } else if (isset($errors ['dataType']) && is_array($errors ['dataType']) && count($errors ['dataType']) > 0) {
            $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
            $result = array(
                CommonConstants::DI_ERROR      => CommonConstants::INVALID_VALUES,
                CommonConstants::DI_PARAMETERS => $errors ['dataType']
            );
            $logger->log(\Phalcon\Logger::ERROR, $apiErrString . " - " . CommonConstants::INVALID_VALUES . " - " . json_encode($result));
        } else {
            $result = $this->getOtherParams($params, $inputParamArr, $redisObj);
        }
        return $result;
    }

    public function getOtherParams($params, $inputParamArr, $redisObj) {
        $userId = (isset($inputParamArr [FieldConstants::USER_ID]) && $inputParamArr [FieldConstants::USER_ID] > 0) ? $inputParamArr [FieldConstants::USER_ID] : 0;
        $userId = (isset($inputParamArr [FieldConstants::EXTERNAL_REF_ID]) && !empty($inputParamArr [FieldConstants::EXTERNAL_REF_ID]) && $userId == 0) ? $inputParamArr [FieldConstants::EXTERNAL_REF_ID] : $userId;
        $params [FieldConstants::AUTH_ID] = (isset($inputParamArr [FieldConstants::USER_ID][0]) && !empty($inputParamArr [FieldConstants::USER_ID][0])) ? $inputParamArr [FieldConstants::USER_ID][0] : "";
        $params [FieldConstants::CREATED_BY] = $this->createdByDetails($userId);
        $params [FieldConstants::UPDATED_BY] = $this->updatedByDetails($userId);
        if ($userId > 0) {
            $params [FieldConstants::USER_OBJ] = $redisObj->getUserObj($userId);
        }
        return array(
            'params' => $params
        );
    }

    /**
     * This function createdByDetails used for encode the data into JSON
     *
     * @param : $authId
     *            contains auth id
     * @author Malini Chandrasekar
     * @return : The function will return the encoded data
     */
    public function createdByDetails($authId) {
        $createdByArr ['REQUEST_METHOD'] = $_SERVER ['REQUEST_METHOD'];
        $createdByArr ['REQUEST_TIME'] = $_SERVER ['REQUEST_TIME'];
        $createdByArr ['HTTP_USER_AGENT'] = $_SERVER ['HTTP_USER_AGENT'];
        $createdByArr ['REMOTE_ADDR'] = $_SERVER ['REMOTE_ADDR'];
        $createdByArr ['REMOTE_USER'] = (isset($_SERVER ['REMOTE_USER'])) ? $_SERVER ['REMOTE_USER'] : '';
        $createdByArr ['PATH_INFO'] = (isset($_SERVER ['PATH_INFO'])) ? $_SERVER ['PATH_INFO'] : '';
        $createdByArr ['USER_ID'] = $authId;
        return json_encode($createdByArr);
    }

    /**
     * This function updatedByDetails used for encode the data into JSON
     *
     * @param : $authId
     *            contains auth id
     * @author Malini Chandrasekar
     * @return : The function will return the encoded data
     */
    public function updatedByDetails($authId) {
        $updatedByArr = json_decode($this->createdByDetails($authId), true);
        return json_encode($updatedByArr);
    }

    public function formAuthLoginArrayToValidate($authId, $jwtObj) {
        $authDetails = json_decode($jwtObj, true);
        $email = "";
        if (isset($authDetails [FieldConstants::EMAIL]) && !empty($authDetails [FieldConstants::EMAIL])) {
            $email = $authDetails [FieldConstants::EMAIL];
        } elseif (isset($authDetails [FieldConstants::COG_USER_NAME]) && !empty($authDetails [FieldConstants::COG_USER_NAME])) {
            $email = $authDetails [FieldConstants::COG_USER_NAME];
        }
        $params [FieldConstants::AUTH_PROFILE] = array(
            $jwtObj
        );
        $params [FieldConstants::EMAIL] = array(
            $email,
            CommonConstants::COM_VALIDATE_EMAIL,
            true,
            ''
        );
        $params [FieldConstants::EXTERNAL_REF_ID] = array(
            $authId,
            '',
            true,
            ''
        );
        $params [FieldConstants::USER_SOCIAL_ID] = array(
            $authDetails [FieldConstants::USER_FACEBOOK_ID],
            CommonConstants::COM_VALIDATE_INT,
            false,
            ''
        );
        $params [FieldConstants::USER_SOCIAL_TYPE] = array(
            1,
            CommonConstants::COM_VALIDATE_INT,
            false,
            ''
        );
        return $params;
    }

}
