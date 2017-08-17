<?php

class PushNotification {

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
            $channel->queue_declare($config [APPLICATION_ENV]->rabbitmq->pushNotificationQueue, false, true, false, false);
            $channel->exchange_declare($config [APPLICATION_ENV]->rabbitmq->pushNotificationExchange, 'fanout', false, true, false);

            $msg = new PhpAmqpLib\Message\AMQPMessage($jsonObj, array(
                'content_type'  => 'text/html charset=UTF-8',
                'delivery_mode' => PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT
            ));
            $channel->queue_bind($config [APPLICATION_ENV]->rabbitmq->pushNotificationQueue, $config [APPLICATION_ENV]->rabbitmq->pushNotificationExchange);
            $channel->basic_publish($msg, $config [APPLICATION_ENV]->rabbitmq->pushNotificationExchange);
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
     * This function is used to send the test push notification
     * @data array $data contains list of input query param.
     * pushNotification
     *
     * @author Malini Chandrasekar
     * @return $channel returns channel
     */
    public function sendTestPushNotification($data, $config) {
        $messageArr ['template_header'] ['device_id'] = $data ['deviceId'];
        $messageArr ['template_content'] ['message'] = "Hi Micheal, Sending the test message";

        return $this->channelMQConnection($config, json_encode($messageArr));
    }

}

?>
