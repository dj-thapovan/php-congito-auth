<?php

class RedisCache {

    public $redis = '';

    /**
     * This is redis constructor
     *
     * @author Malini Chandrasekar
     */
    function __construct() {
        $di = Phalcon\DI::getDefault();
        $this->redis = $di->get("redis");
    }

    /**
     * This is set method for redis key
     *
     * @author Malini Chandrasekar
     *         Inputs are $key and $value
     */
    public function setRedisKey($key, $value) {
        $this->deleteRedisKey($key);
        $key = APPLICATION_ENV . '-' . $key;
        if (!empty($key)) {
            $this->redis->save($key, $value);
        }
        return true;
    }

    /**
     * This is getter method for redis key
     *
     * @author Malini Chandrasekar
     *         Inputs is $key
     */
    public function getRedisKeyValue($key) {
        if (!empty($key)) {
            $key = APPLICATION_ENV . '-' . $key;
            return $this->redis->get($key);
        }
    }

    /**
     * This is delete method for redis key
     *
     * @author Malini Chandrasekar
     *         Inputs is $key
     */
    public function deleteRedisKey($key) {
        if (!empty($key)) {
            $key = APPLICATION_ENV . '-' . $key;
            return $this->redis->save($key, '');
        }
    }

    public function flushRedis() {
        return $this->redis->flush();
    }

    /**
     * This is setter method for user id
     *
     * @author Malini Chandrasekar
     * @return param $authId returns the ID of user
     */
    public function setUserId($authId, $userId = 0) {
        $key = CommonConstants::USER_ID_KEY . $authId;
        $user = new UserModel ();
        if ($userId > 0) {
            // do nothing
        } else {
            $userObj = $user->getUserId($authId);
            if (isset($userObj [FieldConstants::USER_ID]) && $userObj [FieldConstants::USER_ID] > 0) {
                $userId = $userObj [FieldConstants::USER_ID];
            }
        }
        $this->setRedisKey($key, $userId);
        return $userId;
    }

    /**
     * This is get method for user id
     *
     * @author Malini Chandrasekar
     * @return param $userId returns the ID of user
     */
    public function getUserId($authId) {
        $key = CommonConstants::USER_ID_KEY . $authId;
        $userId = $this->getRedisKeyValue($key);
        if (empty($userId) || $userId == null) {
            $userId = $this->setUserId($authId);
        }
        return $userId;
    }

    public function deleteUserId($authId) {
        $key = CommonConstants::USER_ID_KEY . $authId;
        return $this->deleteRedisKey($key);
    }

    /**
     * This is set method for user object
     *
     * @param : $userId
     *            contains id of user and $userArr
     * @author Malini Chandrasekar
     * @return array $userObj returns with user details.
     */
    public function setUserObj($authId, $userArr = array()) {
        $key = CommonConstants::USER_OBJ_KEY . $authId;
        $user = new UserModel ();
        if (count($userArr) > 0) {
            $userObj = $userArr;
        } else {
            $userDetails = $user->getUserInfo($authId, true, true);
            $userObj = (isset($userDetails [0])) ? $userDetails [0] : $userDetails;
        }
        if (!empty($userObj)) {
            $this->setRedisKey($key, json_encode($userObj));
            return $userObj;
        }
        return array();
    }

    /**
     * This is get method for user object
     *
     * @param : $userId
     *            contains id of user
     * @author Malini Chandrasekar
     * @return string $userObj returns with user details.
     */
    public function getUserObj($authId, $flush = false) {
        $key = CommonConstants::USER_OBJ_KEY . $authId;
        $userObj = ($flush) ? '' : $this->getRedisKeyValue($key);
        if (!empty($userObj)) {
            $userObj = json_decode($userObj, true);
        } else {
            $userObj = $this->setUserObj($authId);
        }
        return $userObj;
    }

    public function deleteUserObj($authId) {
        $key = CommonConstants::USER_OBJ_KEY . $authId;
        return $this->deleteRedisKey($key);
    }

}

?>
