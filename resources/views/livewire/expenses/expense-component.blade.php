<div>
    <form method="post" wire:submit="saveExpense">
        <div class="col-md-7 offset-2">

            <div class="mb-3">
                <label>Expense Date</label>
                <input type="text" wire:model="expense_data.expense_date" placeholder="Expense Date"  class="form-control datepicker-basic" >
                @error('expense_data.expense_date') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label>Amount</label>
                <input type="text" wire:model="expense_data.amount"    class="form-control" name="amount" placeholder="Amount"/>
                @error('expense_data.amount') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label>Department</label>
                <select wire:model="expense_data.department_id"  class="form-control"  name="department">
                    <option value="">-Select Department-</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->label }}</option>
                    @endforeach
                </select>
                @error('expense_data.department_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label>Expenses Type</label>
                <select wire:model="expense_data.expenses_type_id"  class="form-control select2"  name="expenses_type_id">
                    <option value="">-Select Type-</option>
                    @foreach($expenses_types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('expense_data.expenses_type_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label>Description / Purpose </label>
                <textarea wire:model="expense_data.purpose"  class="form-control"  name="purpose"></textarea>
            </div>


            <div class="col-lg-12">
                <div class="col-lg-12 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg me-2" wire:loading.attr="disabled">Save

                        <i wire:loading.remove wire:target="saveExpense" class="fa fa-check"></i>

                        <span wire:loading wire:target="saveExpense" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    </button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-danger btn-lg">Cancel</a>
                </div>

            </div>

        </div>
    </form>
</div>
