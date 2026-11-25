<?php

namespace App\Enums;

enum KafkaTopics
{
    const ORDERS = "ORDERS";
    const STOCKS = "STOCKS";
    const GENERAL = "GENERAL";
    const OPAY_KAFKA_TOPIC = "OPAY_KAFKA_TOPIC";
}
