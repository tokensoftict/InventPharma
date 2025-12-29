<div  x-data="product">
    <form method="post" wire:submit="saveStock">
        <div class="row">

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Stock Name</label>
                    <input name="name" required class="form-control" placeholder="Product Name" wire:model="product_data.name" type="text">
                    @error('product_data.name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Location</label>
                    <input name="location"  class="form-control" placeholder="Location" wire:model="product_data.location" type="text">
                    @error('product_data.location') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Code</label>
                    <input name="code"  class="form-control" placeholder="Code" wire:model="product_data.code" type="text">
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3" wire:ignore>
                    <label>Brand</label>
                    <select class="form-control" {{ count($this->brands) > 0 ? 'required' : '' }}  wire:model="product_data.brand_id">
                        <option value="">Choose Brand</option>
                        @foreach($this->brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>


            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3" wire:ignore>
                    <label>Category</label>
                    <select class="form-control" {{ count($this->categories) > 0 ? 'required' : '' }}  wire:model="product_data.category_id">
                        <option value="">Choose Category</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3" wire:ignore>
                    <label>Manufacturers</label>
                    <select class="form-control" {{ count($this->manufacturers) > 0 ? 'required' : '' }} name="category_id" wire:model="product_data.manufacturer_id">
                        <option value="">Choose Manufacturer</option>
                        @foreach($this->manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}">{{ $manufacturer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3" wire:ignore>
                    <label>Classification</label>
                    <select class="form-control" {{ count($this->classifications) > 0 ? 'required' : '' }} name="category_id" wire:model="product_data.classification_id">
                        <option value="">Choose Classification</option>
                        @foreach($this->classifications as $classification)
                            <option value="{{ $classification->id }}">{{ $classification->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3" wire:ignore>
                    <label>Stock Group</label>
                    <select class="form-control" {{ count($this->stockgroups) > 0 ? 'required' : '' }} name="category_id" wire:model="product_data.stockgroup_id">
                        <option value="">Choose Stock Group</option>
                        @foreach($this->stockgroups as $stockgroup)
                            <option value="{{ $stockgroup->id }}">{{ $stockgroup->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12" >
                <div class="mb-3" wire:ignore>
                    <label>Can Product Expiry ?</label>
                    <select class="form-control" required name="expiry" wire:model="product_data.expiry">
                        <option value="">Select One</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('product_data.expiry') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Pieces</label>
                    <input  class="form-control" required placeholder="Pieces" wire:model="product_data.piece" type="text">
                    @error('product_data.piece') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Box</label>
                    <input  class="form-control" required placeholder="Box" wire:model="product_data.box"  type="text">
                    @error('product_data.box') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Carton Content</label>
                    <input  placeholder="Carton Content" required wire:model="product_data.carton" class="form-control" type="number">
                    @error('product_data.carton') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3">
                    <label>Barcode</label>
                    <div class="input-group col-md-12">
                        <input readonly=""  id="text_barcode" type="text"  name="barcode" class="form-control">
                        <div class="input-group-btn">
                            <button id="barcode" type="button" class="btn btn-primary">Capture Barcode</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12" >
                <div class="mb-3" wire:ignore>
                    <label>Sachet Product ?</label>
                    <select class="form-control" required name="sachet" wire:model="product_data.sachet">
                        <option value="">Select One</option>
                        <option value="1">Yes</option>
                        <option selected value="0">No</option>
                    </select>
                    @error('expiry') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="mb-3" wire:ignore>
                    <label>Minimum Quantity</label>
                    <input  placeholder="Minimum Quantity"  wire:model="product_data.minimum_quantity" class="form-control" type="number">
                    @error('product_data.minimum_quantity') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            @if(userCanView('product.setDependentProduct'))
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="mb-3" wire:ignore>
                        <label>Dependent Product</label>
                        <div  class="form-control">
                            <button id="dependentProduct" type="button" class="btn btn-sm btn-primary">Dependent Product Settings</button>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-lg-12">
                <div class="mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" placeholder="Description" wire:model="product_data.description"></textarea>
                </div>
            </div>

            @if(userCanView('product.changeSellingPrice'))
                <div class="col-lg-12">
                    <h4>Product Price Settings</h4>
                    <hr/>
                    <div class="row">
                        @if(department_by_quantity_column('bulksales', false)->status)
                            <div class="col-lg-3 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Bulk Price <span style="color:red;">*</span></label>
                                    <input type="number" required wire:model="product_data.bulk_price" step="0.00001" value=""   class="form-control" name="bulk_price" placeholder="Bulk Price">
                                    @error('product_data.bulk_price') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                        @if(department_by_quantity_column('wholesales', false)->status)
                            <div class="col-lg-3 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Wholesales Price <span style="color:red;">*</span></label>
                                    <input type="number" required wire:model="product_data.whole_price" step="0.00001" value=""   class="form-control" name="whole_price" placeholder="Wholesales Price">
                                    @error('product_data.whole_price') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        @if(department_by_quantity_column('retail', false)->status)
                            <div class="col-lg-3 col-sm-6 col-12">
                                <div class="mb-3">
                                    <label>Retail Sales Price<span style="color:red;">*</span></label>
                                    <input type="number" required  wire:model="product_data.retail_price" step="0.00001" value=""   class="form-control" name="retail_price" placeholder="Retail Sales Price">
                                    @error('product_data.retail_price') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="col-lg-3 col-sm-6 col-12">
                            <div class="mb-3">
                                <label>Bundle Price</label>
                                <div  class="form-control">
                                    <div class="btn-group">
                                        <button type="button" id="otherproductsettings" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            Bundle Price Settings
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="javascript:void(0);" wire:click="openBundlePriceModal('retail')">Retail</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" wire:click="openBundlePriceModal('wholesales')">Wholesales</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


            <div class="col-lg-12 mt-3">
                <h4>Product Image</h4>
                <hr/>
                <div class="mb-3">
                    <label>Product Image</label>
                    <input type="file" id="formFile"  name="logo" wire:model="product_data.image_path" style="width: 0;height: 0;padding: 0; margin: 0" >
                    <div class="form-control">
                        <br/>
                        <img src="{{$this->product_data['image_path'] !== NULL ? (is_string($this->product_data['image_path']) ? asset($this->product_data['image_path']) : $this->product_data['image_path']->temporaryUrl()) : asset('images/brands/placholder.jpg') }}"   class="img-responsive" style="width:15%; margin: auto; display: block;"/>
                        <br/>
                        <div wire:loading wire:target="product_data.image">Uploading...</div>
                        <button type="button" onclick="formFile.click()" class="btn btn-sm btn-success">Select Image and Upload</button>
                    </div>
                    @error('product_data.image_path') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

        </div>

        <div class="col-lg-12">
            <div class="col-lg-12 mt-4">
                <button type="submit" class="btn btn-primary btn-lg me-2" wire:loading.attr="disabled">Save

                    <i wire:loading.remove wire:target="saveStock" class="fa fa-check"></i>

                    <span wire:loading wire:target="saveStock" class="spinner-border spinner-border-sm me-2" role="status"></span>
                </button>
                <a href="{{ route('product.index') }}" class="btn btn-danger btn-lg">Cancel</a>
            </div>

        </div>
    </form>
    <div  class="modal fade" wire:ignore.self id="simpleBarcodeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-3" role="dialog" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Barcode Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h5 class="modal-title">Product Barcode List(s)</h5>
                            <table class="table table-condensed table-bordered">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bar Code</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($this->product->id))
                                    @foreach($this->barcodes as $barcode)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{!! $barcode !!}</td>
                                            <td><button wire:target="deleteBarcode('{{ $barcode }}')" wire:loading.attr="disabled" wire:click="deleteBarcode('{{ $barcode }}')"  class="btn btn-danger btn-sm">
                                                    Delete
                                                    <span wire:loading wire:target="deleteBarcode('{{ $barcode }}')" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveBarcode" wire:target="saveBarcode" wire:loading.attr="disabled" class="btn btn-primary">
                        <span wire:loading wire:target="saveBarcode" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div  class="modal fade" wire:ignore.self id="orderPriceSettings" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-2" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bundle Price Based On Quantity - {{ ucwords($productPriceBasedDepartment) }} Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h5 class="modal-title mb-3">Bundle Price and Quantity List ({{ ucwords($productPriceBasedDepartment) }})</h5>
                            <table class="table table-condensed table-bordered">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody id="priceTableBody">
                                @if(isset($this->product->id))
                                    @foreach($this->productPriceBasedOnQuantity as $price)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><input type="number" value="{{ $price['min_qty'] }}" min="2" class="form-control form-control-sm min_qty" placeholder="Quantity"></td>
                                            <td><input type="number" value="{{ $price['price'] }}" step="0.0000001" min="2" class="form-control form-control-sm min_qty" placeholder="Price"></td>
                                            <td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" id="addPriceBtn" class="float-end btn btn-success btn-sm">
                                            + Add Price
                                        </button>
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveProductPrice" wire:target="saveProductPrice" wire:loading.attr="disabled" class="btn btn-primary">
                        <span wire:loading wire:target="saveProductPrice" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div  class="modal fade" wire:ignore.self id="dependentProductSettings" data-bs-backdrop="static" data-bs-keyboard="false"  role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Configure Dependent Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <legend>Add Product</legend>
                        <hr/>
                        <form wire:submit="addDependentproduct">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3" >
                                        <div wire:ignore>
                                            <select id="dependent_product_select"  wire:model="dependentProduct.stock_id"  class="form-control">
                                                <option value="">-Select Product-</option>
                                            </select>
                                        </div>
                                        @error('dependentProduct.stock_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="mb-3">
                                        <input type="number"  wire:model="dependentProduct.parent"  class="form-control" placeholder="Parent">
                                        @error('dependentProduct.parent') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="mb-3">
                                        <input type="number"  wire:model="dependentProduct.child"  class="form-control" placeholder="Child">
                                        @error('dependentProduct.child') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <button type="submit" id="addDependentproduct" wire:target="addDependentproduct" wire:loading.attr="disabled" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus" wire:loading.remove wire:target="addDependentproduct"></i>
                                        <span wire:loading wire:target="addDependentproduct" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        Add Product
                                    </button>
                                </div>
                            </div>
                        </form>
                    </fieldset>


                    <br/>
                    <fieldset>
                        <legend>Product List</legend>
                        <hr/>
                        <table class="table table-bordered table-hover">
                            <thead>
                            <tr>
                                <th class="text-start">#</th>
                                <th class="text-center">Product Name</th>
                                <th class="text-center" colspan="3">Ratios</th>
                            </tr>
                            <tr>
                                <th class="text-start" colspan="2"></th>
                                <th class="text-center">Parent</th>
                                <th class="text-center">Child</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($product->dependent_products()->with('stock')->get() as $dependentProduct)
                                    <tr>
                                        <td  class="text-start">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $dependentProduct->stock->name }}</td>
                                        <td>{{  $dependentProduct->parent }}</td>
                                        <td>{{  $dependentProduct->child }}</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" wire:confirm="Are you sure, you want to delete this product" wire:click="removeDependentproduct('{{ $dependentProduct->id }}')" wire:target="removeDependentproduct('{{ $dependentProduct->id }}')" wire:loading.attr="disabled">
                                                <i class="fas fa-trash" wire:loading.remove wire:target="removeDependentproduct('{{ $dependentProduct->id }}')"></i>
                                                <span wire:loading wire:target="removeDependentproduct('{{ $dependentProduct->id }}')" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </fieldset>
                </div>
            </div>
        </div>

        <script>
            let barCodeOpen = false;
            let otherproductsettingsModal = "";
            let dependentProductSettingsModal = "";

            window.addEventListener('openBundleSettingsModal', (e) => {
                otherproductsettingsModal.show();
            });
            window.addEventListener('closeBundleSettingsModal', (e) => {
                otherproductsettingsModal.hide();
            });


            window.addEventListener("load", function (){
                $(document).ready(function(){
                    let myModal = "";
                    myModal = new bootstrap.Modal(document.getElementById("simpleBarcodeModal"), {});

                    otherproductsettingsModal = new bootstrap.Modal(document.getElementById("orderPriceSettings"), {});
                    dependentProductSettingsModal = new bootstrap.Modal(document.getElementById("dependentProductSettings"), {});

                    document.getElementById("simpleBarcodeModal").addEventListener('shown.bs.modal', function () {
                        barCodeOpen = true
                    })

                    document.getElementById("simpleBarcodeModal").addEventListener('hidden.bs.modal', function () {
                        barCodeOpen = false
                    })

                    $('#barcode').on('click', function (){
                        myModal.show();
                    });


                    $('#dependentProduct').on('click', function (){
                        dependentProductSettingsModal.show();
                    });

                    document.getElementById("dependentProductSettings").addEventListener('show.bs.modal', function (){
                        var path = '{{ route('findpurchasestock') }}'+"?column={{ '' }}&select2=yes"
                        var obj = this;
                        if (!$('#dependent_product_select').hasClass('select2-hidden-accessible')) {
                            const select2 = $('#dependent_product_select').select2({
                                dropdownParent: $('#dependentProductSettings'),
                                placeholder: 'Select product',
                                width: '100%',
                                ajax: {
                                    url: path,
                                    dataType: 'json',
                                    delay: 250,
                                    data: function (data) {
                                        return {
                                            searchTerm: data.term // search term
                                        };
                                    },
                                    processResults: function (response) {
                                        return {
                                            results:response
                                        };
                                    },
                                }
                            });

                            @this.set("dependentProduct.stock_id",select2.val());
                            select2.on("select2:select", (event) => {
                                @this.set("dependentProduct.stock_id",select2.val());
                            });

                        }
                    })
                });

                $(document).scannerDetection({
                    timeBeforeScanTest: 200, // wait for the next character for upto 200ms
                    endChar: [13], // be sure the scan is complete if key 13 (enter) is detected
                    avgTimeByChar: 40, // it's not a barcode if a character takes longer than 40ms
                    ignoreIfFocusOn: 'input', // turn off scanner detection if an input has focus
                    startChar: [16], // Prefix character for the cabled scanner (OPL6845R)
                    endChar: [40],
                    onComplete: function(barcode){
                        captureBarcode(barcode);
                    }, // main callback function
                    scanButtonKeyCode: 116, // the hardware scan button acts as key 116 (F5)
                    scanButtonLongPressThreshold: 5, // assume a long press if 5 or more events come in sequence
                    onScanButtonLongPressed: function(){
                        alert('key pressed');
                    }, // callback for long pressing the scan button
                    onError: function(string){}
                });

                $('#saveBarcode').on('click', function(){
                    @this.saveBarcode().then(function(response){
                        setTimeout(function(){
                            window.location.reload();
                        },2000)
                    });
                });

            })

            function deleteBarcode(code){
                @this.barcodes = @this.barcodes.filter(item => item !== code)
            }

            function captureBarcode(barcode) {
                if(barCodeOpen === false)
                {
                    alert('Click on capture barcode scanner to capture barcode');
                }else{
                        <?php
                    if(!isset($this->product->id)) {
                        ?>
                    alert('Please save this product before, creating barcode')
                        <?php
                    }else{
                        ?>
                    @this.validateBarcode(barcode).then(function(resp){
                        if(resp.status == false){

                        }
                    });
                        <?php
                    }
                        ?>
                }
            }
        </script>
        <script>
            function getPriceRangeData() {
                const rows = document.querySelectorAll('#priceTableBody tr');

                // ✅ User deleted all rows
                if (rows.length === 0) {
                    return [];
                }

                const data = [];

                // 1. Collect quantity + price from inputs
                rows.forEach(row => {
                    const quantityInput = row.querySelector('input[placeholder="Quantity"]');
                    const priceInput = row.querySelector('input[placeholder="Price"]');

                    const quantity = parseFloat(quantityInput.value);
                    const price = parseFloat(priceInput.value);

                    if (!isNaN(quantity) && !isNaN(price)) {
                        data.push({ quantity, price });
                    }
                });

                // ✅ Rows exist but user cleared all values
                if (data.length === 0) {
                    return [];
                }

                // 2. Sort by quantity (ascending)
                data.sort((a, b) => a.quantity - b.quantity);

                // 3. Build min–max ranges
                return data.map((current, index) => ({
                    min_qty: current.quantity,
                    max_qty: data[index + 1] ? data[index + 1].quantity : 10000,
                    price: current.price
                }));
            }


            document.addEventListener('DOMContentLoaded', function () {
                const addBtn = document.getElementById('addPriceBtn');
                const saveProductPrice = document.getElementById('saveProductPrice');
                const tableBody = document.getElementById('priceTableBody');

                addBtn.addEventListener('click', function () {
                    const row = document.createElement('tr');

                    row.innerHTML = `
            <td></td>
            <td><input type="number" value="" min="2" class="form-control form-control-sm min_qty" placeholder="Quantity"></td>
            <td><input type="number" value="0" step="0.0000001" min="2" class="form-control form-control-sm min_qty" placeholder="Price"></td>
            <td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>
        `;

                    tableBody.appendChild(row);
                    updateRowNumbers();
                });

                tableBody.addEventListener('click', function (e) {
                    if (e.target.classList.contains('delete-row')) {
                        e.target.closest('tr').remove();
                        updateRowNumbers();
                    }
                });

                function updateRowNumbers() {
                    [...tableBody.rows].forEach((row, index) => {
                        row.cells[0].textContent = index + 1;
                    });
                }

                saveProductPrice.addEventListener("click", function (e) {
                    const data = getPriceRangeData();
                        @this.saveProductPrice(data).then((response) => {
                            if (response === true) {
                                otherproductsettingsModal.hide();
                            }
                        })

                });
            });


        </script>
    </div>
</div>