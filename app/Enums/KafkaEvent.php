<?php

namespace App\Enums;

enum KafkaEvent
{
    const ONLINE_PUSH = "ONLINE_PUSH";
    const ONLINE_PULL = "ONLINE_PULL";
    const LOCAL_PUSH = "LOCAL_PUSH";
    const LOCAL_PULL = "LOCAL_PULL";
}
