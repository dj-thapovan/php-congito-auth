<?php

class UserModel {

    /**
     * This function will have the business logic for native sign up
     *
     * @param array $params
     *            contains list of input query params. email, authProfile,externalRegRefId and createdBy
     * @author Malini Chandrasekar
     * @throws exception $ex Throws sql syntax error
     * @return array $ret returns with success, message and corresponding user's objects.
     */
    public function insertUserInfo($params) {
        $di = Phalcon\DI::getDefault();
        $logger = $di->get(CommonConstants::DI_LOGGER);
        $params [FieldConstants::DB_IS_ACTIVE] = 1;
        $params [FieldConstants::CONNECTED_WITH] = NULL;
        if (!empty($params [FieldConstants::USER_SOCIAL_ID])) {
            $params [FieldConstants::CONNECTED_WITH][FieldConstants::USER_SOCIAL_ID] = $params [FieldConstants::USER_SOCIAL_ID];
            $params [FieldConstants::CONNECTED_WITH][FieldConstants::USER_SOCIAL_TYPE] = $params [FieldConstants::USER_SOCIAL_TYPE];
        }
        try {
            $mgrCom = new Commons ();
            $data = array(
                $params [FieldConstants::AUTH_PROFILE],
                $params [FieldConstants::EMAIL],
                $params [FieldConstants::EXTERNAL_REF_ID],
                $params [FieldConstants::CONNECTED_WITH],
                $params [FieldConstants::CREATED_BY]
            );
            $logger->log(\Phalcon\Logger::INFO, __FUNCTION__ . json_encode($data));
            $userDetails = $mgrCom->executeQuery(DatabaseConstants::CALL_SP_USER_AUTHENTICATE, $data);
            $logger->log(\Phalcon\Logger::INFO, __FUNCTION__ . json_encode($userDetails));
            $ret = array();
            $userId = (isset($userDetails ['@id']) && $userDetails ['@id'] > 0) ? $userDetails ['@id'] : 0;
            $age = (isset($userDetails ['@profile_dob']) && $userDetails ['@profile_dob'] > 0) ? $mgrCom->ageCalculator(date('Y-m-d', $userDetails ['@profile_dob'])) : 0;
            if ($age < 18 && $age > 0) {
                $ret [CommonConstants::DI_SUCCESS] = false;
                $ret [CommonConstants::DI_MESSAGE] = CommonConstants::INVALID_USER_AGE_ACCESS_DENIED;
            } elseif ($userId > 0) {
                $ret [CommonConstants::DI_SUCCESS] = true;
                // update the user object details on redis server
                $redisObj = new RedisCache ();
                $redisObj->deleteUserId($params [FieldConstants::EXTERNAL_REF_ID], $userId);
                $redisObj->deleteUserObj($params [FieldConstants::EXTERNAL_REF_ID]);
                $redisObj->deleteUserObj($userId);
                if (isset($userDetails ['@registration_flag']) && $userDetails ['@registration_flag'] == 1) {
                    $ret [CommonConstants::DI_MESSAGE] = CommonConstants::PROFILE_ADDED_SUCCESSFULLY;
                    $ret ["welcomeEmail"] = 1;
                } else {
                    $ret [CommonConstants::DI_MESSAGE] = CommonConstants::USER_LOGGED_IN_SUCCESSFULLY;
                }
            } else {
                $ret [CommonConstants::DI_SUCCESS] = false;
                $ret [CommonConstants::DI_MESSAGE] = CommonConstants::INVALID_USER_INFO;
            }
        } catch (exception $ex) {
            $logger->log(\Phalcon\Logger::ERROR, __FUNCTION__ . $ex->getMessage());
            $ret [CommonConstants::DI_SUCCESS] = false;
            $ret [CommonConstants::DI_MESSAGE] = CommonConstants::SOMETHING_WENT_WRONG;
        }
        return $ret;
    }

}
