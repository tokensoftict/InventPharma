<?php

namespace App\Enums;

enum InvoicePaymentApprovalTypeStatusEnum : string {
    case Approved = 'Approved';
    case Declined = 'Declined';
    case Pending = "Pending";
}