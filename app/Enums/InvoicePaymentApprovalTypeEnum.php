<?php

namespace App\Enums;

enum InvoicePaymentApprovalTypeEnum : string {
    case Cheque = 'Cheque';
    case Credit = 'Credit';

}