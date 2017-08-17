<?php

define('LOG_STARTS', " Starts");
define('LOG_PARAMS', " Params - ");
define('LOG_ERROR', " Error");
define('LOG_MODEL_RESPONSE', " Model Response - ");
define('LOG_END_RESPONSE', " End Response - ");

define('AUTHENTICATE_API', "POST User Authentication");

class LogMsgConstants {

    // Authenticate api - log messages
    const AUTHENTICATE_API_STARTS = AUTHENTICATE_API . LOG_STARTS;
    const AUTHENTICATE_API_PARAMS = AUTHENTICATE_API . LOG_PARAMS;
    const AUTHENTICATE_API_ERROR = AUTHENTICATE_API . LOG_ERROR;
    const AUTHENTICATE_API_MODEL_RESPONSE = AUTHENTICATE_API . LOG_MODEL_RESPONSE;
    const AUTHENTICATE_API_ENDS = AUTHENTICATE_API . LOG_END_RESPONSE;

}
