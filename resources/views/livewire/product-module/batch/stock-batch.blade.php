<div x-data="batch()" x-init="initDatePicker()">
    <h3>Batch List</h3>

    <table class="table table-bordered table-striped table-condensed">
        <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Expiry Date</th>
            <th>{{ $this->dept }}</th>
            <th>Cost Price</th>
            <th>Supplier</th>
        </tr>
        </thead>
        <tbody>
        @foreach($this->batches as $key=>$batch)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $this->stock->name }}</td>
                <td><input type="text"  wire:model="batches.{{ $key}}.expiry_date" class="form-control datepicker-basic"/> </td>
                <td><input type="number" wire:model="batches.{{ $key}}.{{ $selectedDepartment }}" class="form-control"> </td>
                <td><input type="number" step="0.0000001" wire:model="batches.{{ $key}}.{{ cost_price_column(department_by_quantity_column($this->selectedDepartment)->id) }}" class="form-control">
                @if(isset($batch['error']))
                    <span class="font-size-13 text-danger">{{ $batch['error'] }}</span>
                 @endif
                </td>
                <td><select wire:model="batches.{{ $key}}.supplier_id" class="form-control">
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <button type="button" wire:click="batchStock" wire:target="batchStock" wire:loading.attr="disabled" class="btn btn-primary">
        <span wire:loading wire:target="batchStock" class="spinner-border spinner-border-sm me-2" role="status"></span>
        Save Changes
    </button>
    &nbsp; &nbsp;
    <a href="{{ route('product.balance_stock') }}" type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</a>

    <script>
        function batch()
        {
            return {
                initDatePicker(){
                    flatpickr(".datepicker-basic", {  });
                    var e = document.querySelectorAll("[data-trigger]");
                    for (i = 0; i < e.length; ++i) {
                        var a = e[i];
                        new Choices(a, { placeholderValue: "This is a placeholder set in the config", searchPlaceholderValue: "This is a search placeholder" });
                    }
                }
            }
        }
    </script>
</div>
