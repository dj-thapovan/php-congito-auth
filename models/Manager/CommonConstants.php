<?php

class CommonConstants {

    // Key for jwt encryption
    const JWT_KEY = 'Thapovan';
    // common constants
    const EMAIL_NOTIFICATION = 1;
    const SMS_NOTIFICATION = 2;
    const PUSH_NOTIFICATION = 3;
    const DI_LOGGER = "logger";
    const DI_REDIS = "redis";
    const DI_ERROR = "error";
    const DI_SUCCESS = "success";
    const DI_MESSAGE = "message";
    const DI_PARAMS = "params";
    const DI_PARAMETERS = "parameters";
    const DI_BAD_REQUEST = "Bad Request";
    const DI_OK = "Ok";
    // email constants
    const FROM_EMAIL = "from_email";
    const TO = "to";
    const TEMPLATE_NAME = "templateName";
    const TEMPLATE_CONTENT = "templateContent";
    const EMAIL_MESSAGE = "message";
    const EMAIL_MERGE_VARS = "merge_vars";
    const EMAIL_RCPT = "rcpt";
    const EMAIL_VARS = "vars";
    const EMAIL_NAME = "name";
    const EMAIL_CONTENT = "content";
    // validation constants
    const COM_VALIDATE_EMAIL = "email";
    const COM_VALIDATE_USER_ID = "userId";
    const COM_VALIDATE_GENDER = "gender";
    const COM_VALIDATE_INT = "int";
    const COM_VALIDATE_STRING = "string";
    const COM_VALIDATE_FLOAT = "float";
    const COM_VALIDATE_PHONE = "phone";
    const COM_VALIDATE_URL = "url";
    const COM_VALIDATE_DATE = "date";
    const COM_VALIDATE_BOOLEAN = "boolean";
    const COM_VALIDATE_APLHANUMERIC = "alphaNumeric";
    const COM_VALIDATE_ARRAY = "array";
    const COM_VALIDATE_JSON = "json";
    // Field name constants
    const X_AUTH_TOKEN = 'X-Authid';
    const X_JWT_OBJECT = 'X-Jwt-Object';
    // Redis key name constants
    const USER_ID_KEY = 'userId_';
    const USER_OBJ_KEY = 'userObj_';

}
