<?php

class Sms {

    /**
     * This function is for enable channel connection
     *
     * @param : $config
     *            contains list of environment variables of rabbitMQ,
     * @param : $jsonObj
     * @author Malini Chandrasekar
     * @throws exception $ex Throws sql syntax error
     *         return $error
     */
    public function channelMQConnection($config, $jsonObj) {
        $error = '';
        try {
            $connection = new PhpAmqpLib\Connection\AMQPStreamConnection($config [APPLICATION_ENV]->rabbitmq->host, $config [APPLICATION_ENV]->rabbitmq->port, $config [APPLICATION_ENV]->rabbitmq->username, $config [APPLICATION_ENV]->rabbitmq->password);
            $channel = $connection->channel();
            $channel->queue_declare($config [APPLICATION_ENV]->rabbitmq->smsQueue, false, true, false, false);
            $channel->exchange_declare($config [APPLICATION_ENV]->rabbitmq->smsExchange, 'fanout', false, true, false);

            $msg = new PhpAmqpLib\Message\AMQPMessage($jsonObj, array(
                'content_type'  => 'text/html charset=UTF-8',
                'delivery_mode' => PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT
            ));
            $channel->queue_bind($config [APPLICATION_ENV]->rabbitmq->smsQueue, $config [APPLICATION_ENV]->rabbitmq->smsExchange);
            $channel->basic_publish($msg, $config [APPLICATION_ENV]->rabbitmq->smsExchange);
            $channel->close();
            $connection->close();
        } catch (exception $ex) {
            echo $ex->getMessage();
            die();
            $error = $ex->getMessage();
        }
        return $error;
    }

    /**
     * This function is used to send the dummy/test sms
     * @params data Array, config Object
     * @author Dj
     * @return array in the required format
     */
    public function sendTestSms($data, $config) {
        $link = $config->urls->app . "?type=" . $data['urlParams'];
        $messageArr['serviceAgent'] = $data [FieldConstants::PHONE];
        $messageArr['data'] = array(
            "USER_NAME" => $data [FieldConstants::PHONE],
            "URL"       => $link
        );
        $messageArr[CommonConstants::DI_MESSAGE] = "Hi {USER_NAME}, This is a dummy/test message {URL}";
        return $this->channelMQConnection($config, json_encode($messageArr));
    }

}

?>
