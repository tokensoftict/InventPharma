<?php

namespace App\View\Components;

use App\Models\Customer;
use App\Models\Department;
use App\Models\ExpensesType;
use App\Models\Paymentmethod;
use App\Models\Production;
use App\Models\Productionline;
use App\Models\ProductionTemplate;
use App\Models\Rawmaterial;
use App\Models\Status;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\View\Component;

class ReportFilterComponent extends Component
{

    public array $filters;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $data = ['filters'=>$this->filters];

        if(isset($this->filters['status_id'])){
            $data['statuses'] = Status::select('id','name')->get();
        }

        if(isset($this->filters['supplier_id'])){
            $data['suppliers'] = Supplier::where('status',1)->select('id','name','phonenumber')->get();
        }

        if(isset($this->filters['created_by']) || isset($this->filters['user_id']) || isset($this->filters['transfer_by_id']) || isset($this->filters['request_by_id'])){
            $data['users'] = User::where('status',1)->select('id','name')->get();
        }

        if(isset($this->filters['customer_id']) || isset($this->filters['array.customer_id'])) {
            $data['customers'] = Customer::where('status',1)->select('id','firstname','lastname')->get();
        }

        if(isset($this->filters['department_id']) || isset($this->filters['department'])){
            $data['departments'] = Department::where('status',1)->select('id','name','quantity_column')->get();
        }



        if(isset($this->filters['custom_dropdown_id']) && isset($this->filters['items'])){
            $data['items'] = $this->filters['items'];
        }

        if(isset($this->filters['paymentmethod_id'])){
            $data['paymentMethods'] = Paymentmethod::where('status',1)->where('id','<>',6)->select('id','name')->get();
        }

        if(isset($this->filters['stock_id'])){
            $data['stocks'] = Stock::where('status',1)->where('id','<>',6)->select('id','name')->get();
        }


        if(isset($this->filters['department_to']) || isset($this->filters['department_from'])){

            $data['departments'] = Department::where('status',1)->select('id','name','quantity_column', 'label')->whereNotNull('quantity_column')->get();
        }

        if(isset($this->filters['department_from'])){

        }

        if(isset($this->filters['department_to'])){

        }

        if(isset($this->filters['expenses_type_id'])){
            $data['expenses_types'] = ExpensesType::where('status',1)->select('id','name')->get();
        }
        return view('components.report-filter-component', $data);
    }
}
