<?php

namespace App\Policies;

use App\Classes\Settings;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WaitingCustomer;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user) : bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Invoice $invoice) : bool
    {
        //if(!userCanView(type().'view')) return false;

        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user) : bool
    {
        if(!userCanView('invoiceandsales.create')) return false;

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Invoice $invoice) : bool
    {
        return $this->edit($user, $invoice);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function edit(User $user, Invoice $invoice) : bool
    {
        if(!userCanView(type()."edit")) return false;

        if($invoice->onliner_order_id !== NULL) return false;

        if(in_array($invoice->status_id, [
            status('Dispatched'),
            status('Complete'),
            status('Paid'),
            status('Deleted'),
            status('Discount'),
            status('Waiting-For-Credit-Approval'),
            status('Waiting-For-Cheque-Approval'),
        ])) return false;

        return true;
    }


    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function return(User $user, Invoice $invoice) : bool
    {
        if(!userCanView("invoiceandsales.return")) return false;

        if(!in_array($invoice->status_id, [
            status('Dispatched'),
            status('Paid'),
            status('Complete'),
        ])) return false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Invoice $invoice) : bool
    {
        if(!userCanView('invoiceandsales.destroy')) return false;

        if(in_array($invoice->status_id, [
            status('Dispatched'),
            status('Complete'),
            status('Paid'),
            status('Deleted'),
            status('Waiting-For-Credit-Approval'),
            status('Waiting-For-Cheque-Approval'),
        ])) return false;

        return true;

    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function pay(User $user, Invoice $invoice) : bool
    {
        if(!userCanView('payment.createInvoicePayment') && !userCanView('payment.create')) return false;

        if($invoice->onliner_order_id !== NULL){ //if it is an online invoice
            if($invoice->status_id === status("Draft"))  return false;
            if($invoice->status_id === status('Packed-Waiting-For-Payment')) return true;
        }

        if($invoice->status_id !== status("Draft"))  return false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function printAfour(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.print_afour")) return false;

        if($invoice->onliner_order_id !== NULL){ //if it is an online invoice
            if($invoice->status_id === status("Draft"))  return false;
            if($invoice->status_id === status('Packing')) return true;
            if($invoice->status_id === status('Packed-Waiting-For-Payment')) return true;
        }

        if(!userCanView('invoiceandsales.rePrintInvoice') && !canPrint(Settings::$printType['a4'], $invoice)) return false;

        if(
            $invoice->status_id !== status("Dispatched") &&
            $invoice->status_id !== status("Paid") &&
            $invoice->status_id !== status("Complete")
        )  return false;

        if($invoice->status_id ==  status('Deleted')    ||
            $invoice->status_id == status('Waiting-For-Credit-Approval') ||
            $invoice->status_id == status('Waiting-For-Cheque-Approval')) return false;

        return true;
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function printThermal(User $user, Invoice $invoice)
    {
        if(!userCanView(type()."pos_print")) return false;

        if($invoice->onliner_order_id !== NULL){ //if it is an online invoice
            if($invoice->status_id === status("Draft"))  return false;
            if($invoice->status_id === status('Packing')) return true;
            if($invoice->status_id === status('Packed-Waiting-For-Payment')) return true;
        }

        if(!userCanView('invoiceandsales.rePrintInvoice') && !canPrint(Settings::$printType['thermal'], $invoice)) return false;

        if($invoice->department === 'retail' && $invoice->retail_printed === true && !userCanView('invoiceandsales.rePrintInvoice')) return false;

        if($invoice->department === 'retail' && ($invoice->status_id !== status('Complete') && $invoice->status_id !== status('Paid') && $invoice->online_order_status !="1")) return false;

        if($invoice->status_id ==  status('Deleted')    ||
            $invoice->status_id == status('Waiting-For-Credit-Approval') ||
            $invoice->status_id == status('Waiting-For-Cheque-Approval')) return false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function printWaybill(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.print_way_bill")) return false;

        if( $invoice->status_id == status('Waiting-For-Credit-Approval') &&
            $invoice->status_id == status('Waiting-For-Cheque-Approval')) return false;

        if(!userCanView('invoiceandsales.rePrintInvoice') && !canPrint(Settings::$printType['waybill'], $invoice)) return false;

        if(
            $invoice->status_id !== status("Dispatched") &&
            $invoice->status_id !== status("Paid") &&
            $invoice->status_id !== status('Complete')
        )  return false;

        if($invoice->status_id ==  status('Deleted')    ||
            $invoice->status_id == status('Waiting-For-Credit-Approval') ||
            $invoice->status_id == status('Waiting-For-Cheque-Approval')) return false;

        return true;
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function dispatched(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.dispatchInvoice")) return false;

        if($invoice->status_id !== status("Paid"))  return false;

        return true;
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function applyProductDiscount(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.applyProductDiscount")) return false;

        if($invoice->status_id !== status("Discount"))  return false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function applyInvoiceDiscount(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.applyInvoiceDiscount")) return false;

        if($invoice->status_id !== status("Discount"))  return false;

        return true;
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function showPayment(User $user, Invoice $invoice)
    {
        //if(!userCanView("invoiceandsales.applyProductDiscount")) return false;

        if(!(in_array($invoice->status_id, [
            status("Paid")
            ,status("Dispatched"),
            status('Complete'),
        ])))  return false;

        return true;
    }


    /**]
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function applyForCredit(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.applyForCredit")) return false;

        if($invoice->status_id === status('Draft')) return true;

        return false;
    }


    /**
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function applyForCheque(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.applyForCheque")) return false;

        if($invoice->status_id === status('Draft')) return true;

        return false;
    }

    /**
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function approveCreditPayment(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.approve_or_decline_credit_payment")) return false;

        if($invoice->status_id === status('Waiting-For-Credit-Approval')) return true;

        return false;
    }

    /**
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function approveChequePayment(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.approve_or_decline_cheque_payment")) return false;

        if($invoice->status_id === status('Waiting-For-Cheque-Approval')) return true;

        return false;
    }


    /**
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function processOnlineInvoice(User $user, Invoice $invoice)
    {
        if($invoice->onliner_order_id === NULL) return false; //check if this is an online invoice

        if(!userCanView("invoiceandsales.processOnlineInvoice")) return false;

        if($invoice->status_id === status('Draft')) return true;

        return false;
    }


    /**
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function packOnlineInvoice(User $user, Invoice $invoice)
    {
        if($invoice->onliner_order_id === NULL) return false; //check if this is an online invoice

        if($invoice->status_id !== status('Packing')) return false;

        if(!userCanView("invoiceandsales.packOnlineInvoice")) return false;

        return true;
    }


    public function canAddToWaitingList(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.addToWaitingList")) return false;

        if(isset($invoice->waitingCustomer->entered_at)) return false; // if already in waiting list

        if($invoice->status_id != status("Paid"))  return false;

        return true;
    }

    public function canRemoveToWaitingList(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.removeFromWaitingList")) return false;

        if(isset($invoice->waitingCustomer->entered_at)) return true; // if already in waiting list

        return false;
    }

    public function setWaitingListInvoiceToPicking(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.pickWaitingListInvoice")) return false;

        if(!isset($invoice->waitingCustomer->entered_at)) return false;

        if($invoice->waitingCustomer->status === WaitingCustomer::$waitingInvoiceStatus['waiting']) return true;

        return false;
    }


    public function setWaitingListInvoiceToCompletePicking(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.packWaitingListInvoice")) return false;

        if(!isset($invoice->waitingCustomer->entered_at)) return false;

        if($invoice->waitingCustomer->status === WaitingCustomer::$waitingInvoiceStatus['picking']) return true;

        return false;
    }

    public function setWaitingListInvoiceToPacking(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.packWaitingListInvoice")) return false;

        if(!isset($invoice->waitingCustomer->entered_at)) return false;

        if($invoice->waitingCustomer->status === WaitingCustomer::$waitingInvoiceStatus['complete_picking']) return true;

        return false;
    }


    public function setWaitingListInvoiceToPacked(User $user, Invoice $invoice)
    {
        if(!userCanView("invoiceandsales.packedWaitingListInvoice")) return false;

        if(!isset($invoice->waitingCustomer->entered_at)) return false;

        if($invoice->waitingCustomer->status === WaitingCustomer::$waitingInvoiceStatus['packing']) return true;

        return false;
    }
}
