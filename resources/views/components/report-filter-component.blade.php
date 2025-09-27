<div>
    <form action="" method="post" class="border-bottom">
        @csrf
        <div class="row">

            @if(isset($filters['from']))
                <div class="col-auto">
                    <div class="mb-3">
                        <label class="form-label">From</label>
                        <input type="text" value="{{ $filters['from'] }}" class="form-control datepicker-basic" name="filter[from]" id="datepicker-basic">
                    </div>
                </div>
            @endif

            @if(isset($filters['to']))
                <div class="col-auto">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <input type="text" value="{{ $filters['to'] }}" class="form-control datepicker-basic" name="filter[to]" id="datepicker-basic">
                    </div>
                </div>
            @endif

            @if(isset($filters['invoice_date']))
                <div class="col-auto">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" value="{{ $filters['invoice_date'] }}" class="form-control datepicker-basic" name="filter[invoice_date]" id="datepicker-basic">
                    </div>
                </div>
            @endif

            @if(isset($filters['status_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">System Status</label>
                        <select class="form-control"  data-trigger name="filter[status_id]" id="choices-single-default" placeholder="Select Status">
                            @foreach($statuses as $status)
                                <option {{ $filters['status_id'] == $status->id ? 'selected' : '' }} value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            @if(isset($filters['supplier_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-control" data-trigger name="filter[supplier_id]" id="choices-single-default" placeholder="Select Supplier">
                            @foreach($suppliers as $supplier)
                                <option {{ $filters['supplier_id'] == $supplier->id ? 'selected' : '' }} value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['customer_id']))
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-control select2Product"  name="filter[customer_id]" id="choices-single-default" placeholder="Select Customer">
                            @if(isset($filters['customer']))
                                <option selected value="{{ $filters['customer']->id }}">{{ $filters['customer']->firstname }} {{ $filters['customer']->lastname }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['array.customer_id']))
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-control select2Product" multiple name="filter[customer_id][]" id="choices-single-default" placeholder="Select Customers">
                            @foreach($customers as $customer)
                                @if(in_array($customer->id, $filters['array.customer_id']))
                                    <option selected value="{{ $customer->id }}">{{ $customer->firstname }} {{ $customer->lastname }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['department_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-control" data-trigger name="filter[department_id]" id="choices-single-default" placeholder="Select Department">
                            @foreach($departments as $department)
                                <option {{ $filters['department_id'] == $department->id ? 'selected' : '' }} value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['department']) || isset($filters['in_department']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-control" data-trigger name="filter[department]" id="choices-single-default" placeholder="Select Department">
                            @foreach($departments as $department)
                                <option {{ $filters['department'] == $department->quantity_column ? 'selected' : '' }} value="{{ $department->quantity_column }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['created_by']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">System User</label>
                        <select class="form-control" data-trigger name="filter[created_by]" id="choices-single-default" placeholder="Select System User">
                            @foreach($users as $user)
                                <option {{ $filters['created_by'] == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            @if(isset($filters['user_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">System User</label>
                        <select class="form-control" data-trigger name="filter[user_id]" id="choices-single-default" placeholder="Select System User">
                            @foreach($users as $user)
                                <option {{ $filters['user_id'] == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['custom_dropdown_id']) && isset($filters['items']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">{{ $filters['label_name'] }}</label>
                        <select class="form-control select2"  name="filter[custom_dropdown_id]" id="choices-single-default" placeholder="Select {{ $filters['label_name'] }}">
                            @foreach($filters['items'] as $item)
                                <option {{ $filters['custom_dropdown_id'] ==  $item['id'] ? 'selected' : '' }} value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['transfer_by_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">System User</label>
                        <select class="form-control" data-trigger name="filter[transfer_by_id]" id="choices-single-default" placeholder="Select System User">
                            @foreach($users as $user)
                                <option {{ $filters['transfer_by_id'] == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['request_by_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">System User</label>
                        <select class="form-control" data-trigger name="filter[request_by_id]" id="choices-single-default" placeholder="Select System User">
                            @foreach($users as $user)
                                <option {{ $filters['request_by_id'] == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif




            @if(isset($filters['purchase_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Raw Material</label>
                        <select class="form-control" data-trigger name="filter[purchase_id]" id="choices-single-default" placeholder="Select Raw Material">
                            @foreach($materials as $material)
                                <option {{ $filters['purchase_id'] == $material->id ? 'selected' : '' }} value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif



            @if(isset($filters['paymentmethod_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" data-trigger name="filter[paymentmethod_id]" id="choices-single-default" placeholder="Select Payment Method">
                            @foreach($paymentMethods as $paymentMethod)
                                <option {{ $filters['paymentmethod_id'] == $paymentMethod->id ? 'selected' : '' }} value="{{ $paymentMethod->id }}">{{ $paymentMethod->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            @if(isset($filters['stock_id']))
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Search Product</label>
                        <select class="form-control select2Product" name="filter[stock_id]" id="choices-single-default" placeholder="Select Product">
                            @if(isset($filters['stock']))
                                <option selected value="{{ $filters['stock']->id }}">{{ $filters['stock']->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            @endif



            @if(isset($filters['production_template_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Product Template</label>
                        <select class="form-control" data-trigger name="filter[production_template_id]" id="choices-single-default" placeholder="Select Product Template">
                            @foreach($templates as $template)
                                <option {{ $filters['production_template_id'] == $template->id ? 'selected' : '' }} value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            @if(isset($filters['productionline_id']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Production Line / Tank</label>
                        <select class="form-control" data-trigger name="filter[productionline_id]" id="choices-single-default" placeholder="Select Production Line / Tank">
                            @foreach($lines as $line)
                                <option {{ $filters['productionline_id'] == $line->id ? 'selected' : '' }} value="{{ $line->id }}">{{ $line->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            @if(isset($filters['department_from']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">From</label>
                        <select class="form-control" data-trigger name="filter[department_from]" id="choices-single-default" placeholder="Select Source Department">
                            @foreach($departments as $department)
                                <option {{ $filters['department_from'] == $department->quantity_column ? 'selected' : '' }} value="{{ $department->quantity_column }}">{{ $department->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            @if(isset($filters['department_to']))
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <select class="form-control" data-trigger name="filter[department_to]" id="choices-single-default" placeholder="Select Destination Department">
                            @foreach($departments as $department)
                                <option {{ $filters['department_to'] == $department->quantity_column ? 'selected' : '' }} value="{{ $department->quantity_column }}">{{ $department->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if(isset($filters['expenses_type_id']))
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Expenses Type</label>
                        <select class="form-control" data-trigger name="filter[expenses_type_id]" id="choices-single-default" placeholder="Select Expense Type">
                            @foreach($expenses_types as $expenses_type)
                                <option {{ $filters['expenses_type_id'] == $expenses_type->id ? 'selected' : '' }} value="{{ $expenses_type->id }}">{{ $expenses_type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif


            <div class="col-auto">
                <button type="submit" class="btn btn-primary mt-4">Filter</button>
            </div>
        </div>
    </form>
    <br/>
</div>

