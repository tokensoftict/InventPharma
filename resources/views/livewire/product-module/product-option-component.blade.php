<div  x-data="stockOption">


    <div  class="modal fade" wire:ignore.self id="productOptionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <form wire:submit="{{ $stockOptionAction == "create" ? 'submitOptionValue' : 'updateOptionValue('.$updatingStockOptionValue.')' }}"  method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Option Value</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="input-modal-option-value" class="form-label">Option Value</label>
                            <select name="option_field_value_id" wire:model="option_field_value_id" id="input-modal-option-value" class="form-select">
                                <option value="">Select Option</option>
                                @foreach($optionValues as $optionValue)
                                    <option {{ ($stockOptionAction == "update" and $optionValue->id == $option_field_value_id) ? "selected" : "" }} value="{{ $optionValue->id }}">{{ $optionValue->name }}</option>
                                @endforeach
                            </select>
                            @error("option_field_value_id") <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="input-modal-price" class="form-label">Retail Price</label>
                            <div class="input-group">
                                <select name="retail_status" wire:model="retail_status" class="form-select">
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <select name="retail_price_prefix" wire:model="retail_price_prefix" class="form-select">
                                    <option value="+">+</option>
                                    <option value="-">-</option>
                                </select>
                                <input type="text" name="retail_price" wire:model="retail_price" value="0" placeholder="Price" id="input-modal-price" class="form-control">
                            </div>
                            @error("retail_price_prefix") <span class="text-danger">{{ $message }}</span> @enderror
                            @error("retail_price") <span class="text-danger">{{ $message }}</span> @enderror
                        </div>


                        <div class="mb-3">
                            <label for="input-modal-price" class="form-label">Wholesales Price</label>
                            <div class="input-group">
                                <select name="wholesales_status" wire:model="wholesales_status" class="form-select">
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <select name="wholesales_price_prefix" wire:model="wholesales_price_prefix" class="form-select">
                                    <option value="+">+</option>
                                    <option value="-">-</option>
                                </select>
                                <input type="text" name="wholesales_price" wire:model="wholesales_price" value="0" placeholder="Price" id="input-modal-price" class="form-control">
                            </div>
                            @error("wholesales_price_prefix") <span class="text-danger">{{ $message }}</span> @enderror
                            @error("wholesales_price") <span class="text-danger">{{ $message }}</span> @enderror
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="submitOptionValue" wire:target="submitOptionValue,updateOptionValue" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="submitOptionValue,updateOptionValue" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>



    @foreach($stockOptions as $stockOption)
        <fieldset id="option-row-{{ $stockOption->id }}" class="mb-2">
            <legend>{{ $stockOption->option_field->name }}</legend>
            <div class="row align-items-center">
                <div class="col-sm-11">
                    <div class="mb-3">
                        <label  class="form-label">Required</label>
                        <select class="form-select">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th class="text-end">Option Value</th>
                                <th class="text-end">Retail Status</th>
                                <th class="text-end">Retail Price</th>
                                <th class="text-end">Wholesales Status</th>
                                <th class="text-end">Wholesales Price</th>
                                <th class="text-end">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($stockOption->stock_option_values as $option_value)
                                <tr>
                                    <td class="text-end">{{ $loop->iteration }}</td>
                                    <td class="text-end">{{ $option_value->option_field_value->name }}</td>
                                    <td class="text-end">{{ $option_value->retail_status == "1" ? "Enabled" : "Disabled" }}</td>
                                    <td class="text-end">{{ $option_value->retail_price_prefix.money($option_value->retail_price) }}</td>
                                    <td class="text-end">{{ $option_value->wholesales_status == "1" ? "Enabled" : "Disabled" }}</td>
                                    <td class="text-end">{{ $option_value->wholesales_price_prefix.money($option_value->wholesales_price) }}</td>
                                    <td class="text-end">
                                        <button type="button" data-bs-toggle="tooltip" wire:target="editStockOptionValues" wire:loading.attr="disabled" wire:click="editStockOptionValues('{{ $option_value->id }}')" data-option-row="0" data-option-value-row="0" class="btn btn-primary" aria-label="Edit" data-bs-original-title="Edit">
                                            <i wire:loading.remove wire:target="editStockOptionValues" class="fas fa-solid fa-edit"></i>
                                            <span wire:loading wire:target="editStockOptionValues" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        </button>
                                        <button type="button" wire:target="deleteStockOptionValues" wire:confirm="Are you sure you want to delete this option?" wire:loading.attr="disabled" wire:click="deleteStockOptionValues('{{ $option_value->id }}')"  data-bs-toggle="tooltip" class="btn btn-danger" aria-label="Remove" data-bs-original-title="Remove">
                                            <i wire:loading.remove wire:target="deleteStockOptionValues" class="fas fa-solid fa-minus-circle"></i>
                                            <span wire:loading wire:target="deleteStockOptionValues" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="4"></td>
                                <td class="text-end">
                                    <button type="button"  wire:target="newStockOptionValues" wire:loading.attr="disabled" wire:click="newStockOptionValues('{{ $stockOption->option_field_id }}', '{{ $stockOption->id }}')" data-bs-toggle="tooltip" title="Add Option Value" data-option-row="0" class="btn btn-primary">
                                        <span wire:loading wire:target="newStockOptionValues" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        <i wire:loading.remove wire:target="newStockOptionValues" class="fas fa-solid fa-plus-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col">
                    <button type="button" wire:target="deleteStockOption" wire:loading.attr="disabled" wire:confirm="Are you sure you want to delete this option?"  wire:click="deleteStockOption('{{ $stockOption->id }}')" class="btn btn-danger confirm-text" data-bs-toggle="tooltip" aria-label="Remove" data-bs-original-title="Remove">
                        <i wire:loading.remove wire:target="deleteStockOption" class="fas fa-solid fa-minus-circle"></i>
                    </button>
                </div>
            </div>
        </fieldset>
    @endforeach

    <hr/>
    <fieldset>
        <legend>Add New Product Option</legend>
        <form method="post" wire:submit="submitOption" >
            <div class="row mb-4">
                <div class="col-lg-3 col-sm-6 col-12" >
                    <div wire:ignore>
                        <label>Select Option</label>
                        <select wire:model="option" name="option" class="form-control select2" x-init="select2" >
                            <option value="">Select Option</option>
                            @foreach($options as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error("option") <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-lg-3 col-sm-6 col-12">
                    <br/>
                    <button type="submit" wire:target="submitOption" wire:loading.attr="disabled" class="btn btn-primary btn-sm mt-2">
                        <span wire:loading wire:target="submitOption" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Add Option
                    </button>
                </div>
            </div>
            <hr/>
        </form>
    </fieldset>



    <script>
        function stockOption()
        {
            return {
                select2()
                {
                    var select2 = $('.select2').select2({
                        placeholder: 'Select Option',
                    });

                    @this.set("option",select2.val());
                    select2.on("select2:select", (event) => {
                        @this.set("option",select2.val());
                    });
                },

            }
        }


    </script>
    <script>
        window.addEventListener('load', function (){
            let myModal = "";
            $(document).ready(function(){
                myModal = new bootstrap.Modal(document.getElementById("productOptionModal"), {});
            });
            window.addEventListener('openModal', (e) => {
                myModal.show();
            });
            window.addEventListener('closeModal', (e) => {
                myModal.hide();
            });

        });
    </script>
</div>
