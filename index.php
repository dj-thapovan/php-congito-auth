<?php

ini_set('display_errors', 'off');
ini_set('memory_limit', -1);
ini_set('max_execution_time', 300);

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Firebase\JWT\JWT;
use Phalcon\Logger\Adapter\Syslog as SyslogAdapter;
use Phalcon\Logger\Adapter\Stream as StreamAdapter;
use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\Frontend\Data as FrontData;

$loader = new Loader ();
require_once __DIR__ . '/libraries/vendor/firebase/php-jwt/src/JWT.php';
require_once __DIR__ . '/libraries/vendor/autoload.php';
require_once __DIR__ . '/libraries/rabbitmq/autoload.php';
require_once __DIR__ . '/libraries/stripe/init.php';

global $config;
$config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/config/config.ini');

$loader->registerDirs([
    __DIR__ . "/models/",
    __DIR__ . "/models/Account",
    __DIR__ . "/models/Utils",
    __DIR__ . "/libraries/",
    __DIR__ . "/libraries/Mandrill",
    __DIR__ . "/libraries/vendor/",
    __DIR__ . "/libraries/aws/",
    __DIR__ . "/libraries/rabbitmq/",
    __DIR__ . "/routes/"
])->register();

$di = new FactoryDefault ();

$di->set('modelsManager', function () {
    return new Phalcon\Mvc\Model\Manager ();
});

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));

$di->set('db', function () use ($config) {
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        "host"     => $config [APPLICATION_ENV]->database->host,
        "port"     => $config [APPLICATION_ENV]->database->port,
        "username" => $config [APPLICATION_ENV]->database->username,
        "password" => $config [APPLICATION_ENV]->database->password,
        "dbname"   => $config [APPLICATION_ENV]->database->name,
        "charset"  => 'utf8',
        "options"  => array(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES utf8'
        )
    ));
});

$di->set('logger', function () use ($config) {
    if (strcmp($config [APPLICATION_ENV]->logger->out, "stdout") === 0) {
        $logger = new StreamAdapter("php://stdout", array(
            'option'   => LOG_CONS | LOG_NDELAY | LOG_PID,
            'facility' => LOG_USER | LOG_MAIL
        ));
    } else {
        $logger = new SyslogAdapter("thapovan-log-" . date('Y-m-d'), array(
            'option'   => LOG_CONS | LOG_NDELAY | LOG_PID,
            'facility' => LOG_USER | LOG_MAIL
        ));
    }
    return $logger;
});

$di->set('redis', function () use ($config) {
    // Cache data for 6 hours
    $frontCache = new FrontData(
        [
        "lifetime" => 21600,
        ]
    );
    // Create the Cache setting redis connection options
    $redis = new Redis(
        $frontCache, [
        "host"       => $config [APPLICATION_ENV]->redis->host,
        "port"       => $config [APPLICATION_ENV]->redis->port,
        //"auth"       => "",
        "persistent" => false,
        "index"      => $config [APPLICATION_ENV]->redis->db,
        ]
    );
    return $redis;
});

$app = new Micro ();

/**
 * @SWG\Post(path="/authenticate",
 * description="This function is used to create an entry in the user table while completing the registration process.
 * Welcome email will be sent to the user.",
 * operationId="authenticate",
 * produces={"application/json"},
 * @SWG\Parameter(
 * name="X-AUTH-TOKEN",
 * description="Logged in user's JWT token",
 * in="header",
 * required=true,
 * type="string"
 * ),
 * @SWG\Response(
 * response=200,
 * description="User details stored into database",
 * @SWG\Schema(ref="#/definitions/successModel")
 * ),
 * @SWG\Response(response=400, description="Bad Request"),
 * @SWG\Response(
 * response="default",
 * description="Unexpected error",
 * @SWG\Schema(ref="#/definitions/ErrorModel")
 * )
 * )
 */
$app->post(ActionConstants::AUTHENTICATE_URL, function () use ($app, $config) {
    $di = Phalcon\DI::getDefault();
    $logger = $di->get(CommonConstants::DI_LOGGER);
    $request = new Phalcon\Http\Request ();
    $authId = ($request->getHeader(CommonConstants::X_AUTH_TOKEN)) ? $request->getHeader(CommonConstants::X_AUTH_TOKEN) : '';
    $logger->log(\Phalcon\Logger::INFO, LogMsgConstants::AUTHENTICATE_API_STARTS);
    $result [CommonConstants::DI_SUCCESS] = false;
    $jwtObj = ($request->getHeader(CommonConstants::X_JWT_OBJECT)) ? $request->getHeader(CommonConstants::X_JWT_OBJECT) : '';
    $mgrCom = new Commons ();
    $params = $mgrCom->formAuthLoginArrayToValidate($authId, $jwtObj);
    $formValidation = $mgrCom->formFieldValidation($params, $app, $logger, LogMsgConstants::AUTHENTICATE_API_ERROR);
    $logger->log(\Phalcon\Logger::INFO, LogMsgConstants::AUTHENTICATE_API_PARAMS . json_encode($params));
    if (isset($formValidation [CommonConstants::DI_ERROR]) && !empty($formValidation [CommonConstants::DI_ERROR])) {
        $result = $formValidation;
    } else {
        $data = $formValidation [CommonConstants::DI_PARAMS];
        // implementation code starts here
        $user = new UserModel ();
        $userObj = $user->insertUserInfo($data);
        $logger->log(\Phalcon\Logger::INFO, LogMsgConstants::AUTHENTICATE_API_MODEL_RESPONSE . json_encode($userObj));
        if (!empty($userObj) && $userObj [CommonConstants::DI_SUCCESS]) {
            $app->response->setStatusCode(200, CommonConstants::DI_OK)->sendHeaders();
            $result = array(
                CommonConstants::DI_SUCCESS => 1,
                CommonConstants::DI_MESSAGE => $userObj [CommonConstants::DI_MESSAGE]
            );
        } else {
            $app->response->setStatusCode(400, CommonConstants::DI_BAD_REQUEST)->sendHeaders();
            $result = array(
                CommonConstants::DI_ERROR   => $userObj [CommonConstants::DI_MESSAGE],
                CommonConstants::DI_SUCCESS => 0
            );
        }
    }
    $logger->log(\Phalcon\Logger::INFO, LogMsgConstants::AUTHENTICATE_API_ENDS . json_encode($result));
    echo json_encode($result);
    return;
});

$app->get('/test/redis', function() use ($app) {
    $di = Phalcon\DI::getDefault();
    $request = new Phalcon\Http\Request ();
    $authId = ($request->getHeader(CommonConstants::X_AUTH_TOKEN)) ? $request->getHeader(CommonConstants::X_AUTH_TOKEN) : '';
    $redisObj = new RedisCache();
    //$redisObj->flushRedis()
    //$redisObj->deleteUserObj($authId);
    $authObj = $redisObj->getUserObj($authId);
    echo json_encode($authObj);
});

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo CommonConstants::PAGE_NOT_FOUND;
});

function defaultExceptionHandler($exception) {
    try {
        if ($exception instanceof Throwable) {
            $errorMsg = array(
                CommonConstants::DI_SUCCESS => false,
                CommonConstants::DI_MESSAGE => $exception->__toString()
            );
            echo json_encode($errorMsg);
            die();
        } else {
            print_r($exception);
        }
    } catch (Exception $e) {
        print_r($e->__toString());
    }
}

// set_exception_handler('defaultExceptionHandler');
// set_error_handler('defaultExceptionHandler');
$app->handle();
