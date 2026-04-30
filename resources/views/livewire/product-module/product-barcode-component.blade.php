<div>
    <div class="row">
        @if($product->stockbarcodes->count() > 0)
            <!-- Settings Form -->
            <div class="col-lg-4 col-md-6 col-sm-12 d-print-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Barcode Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Select Barcode</label>
                            <select class="form-control" wire:model.live="selectedBarcode">
                                @foreach($product->stockbarcodes as $barcode)
                                    <option value="{{ $barcode->barcode }}">{{ $barcode->barcode }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Label Size</label>
                            <select class="form-control" wire:model.live="labelSize">
                                @foreach($availableSizes as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Number of Copies</label>
                            <input type="number" class="form-control" wire:model.live="numberOfCopies" min="1" max="100">
                        </div>

                        <button type="button" class="btn btn-primary w-100 mt-3" onclick="printBarcode()"
                            wire:loading.attr="disabled" wire:target="numberOfCopies, selectedBarcode, labelSize">
                            <i class="fas fa-print" wire:loading.remove
                                wire:target="numberOfCopies, selectedBarcode, labelSize"></i>
                            <span wire:loading wire:target="numberOfCopies, selectedBarcode, labelSize"
                                class="spinner-border spinner-border-sm me-2"></span>
                            Print Barcode
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Area -->
            <div class="col-lg-8 col-md-6 col-sm-12 d-print-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Preview</h5>
                    </div>
                    <div class="card-body text-center"
                        style="background: #f4f6f9; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 250px;">
                        @if($selectedBarcode)
                            <div style="background: #fff; border: 1px solid #ddd; padding: 10px; display: inline-block;">
                                <div>{!! DNS1D::getBarcodeSVG($selectedBarcode, 'C128', 2, 100, 'black', false) !!}</div>
                                <div style="font-size: 16px; font-weight: bold; margin-top: 5px; letter-spacing: 2px;">
                                    {{ $selectedBarcode }}</div>
                            </div>
                        @else
                            <p class="text-muted">Please select a barcode.</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="col-12 d-print-none">
                <div class="alert alert-warning d-flex flex-column align-items-start">
                    <div>No barcodes found for this product. Please capture barcodes in the General tab first, or generate a
                        system barcode.</div>
                    <button type="button" class="btn btn-success mt-3" wire:click="generateBarcode"
                        wire:loading.attr="disabled">
                        <i class="fas fa-barcode"></i> Generate System Barcode
                        <span wire:loading wire:target="generateBarcode" class="spinner-border spinner-border-sm ms-2"
                            role="status"></span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Print Layout Area (Hidden on screen) -->
    @if($selectedBarcode)
        <div id="barcode-print-area" style="display: none;"
            wire:key="print-area-{{ $numberOfCopies }}-{{ $selectedBarcode }}">@for($i = 0; $i < $numberOfCopies; $i++)
                <div class="label-container {{ $i < $numberOfCopies - 1 ? 'page-break' : '' }}"
                    wire:key="label-{{ $i }}-{{ $selectedBarcode }}">
                    <div class="barcode-wrapper">{!! DNS1D::getBarcodeSVG($selectedBarcode, 'C128', 2, 60, 'black', false) !!}
                        <div class="barcode-text">{{ $selectedBarcode }}</div>
                    </div>
            </div>@endfor
        </div>
    @endif

    <script>
        function printBarcode() {
            var printContents = document.getElementById('barcode-print-area').innerHTML;
            var printWindow = window.open('', '_blank', 'width=600,height=400');

            var labelSize = "{{ $labelSize }}";
            var width = "50mm";
            var height = "25mm";
            var orientation = "landscape";

            if (labelSize === '50x25') { width = '50mm'; height = '25mm'; }
            if (labelSize === '40x30') { width = '40mm'; height = '30mm'; }
            if (labelSize === '50x30') { width = '50mm'; height = '30mm'; }
            if (labelSize === '60x40') { width = '60mm'; height = '40mm'; }

            printWindow.document.write('<html><head><title>Print Barcode</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('html, body { margin: 0; padding: 0; background: #fff; height: 100%; overflow: hidden; }');
            printWindow.document.write('.label-container { width: ' + width + '; height: ' + height + '; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; padding: 1mm 2mm; margin-top:3mm; margin-left:1mm;  overflow: hidden; position: relative; }');
            printWindow.document.write('.barcode-wrapper { text-align: center; width: 100%; }');
            printWindow.document.write('.barcode-wrapper svg { width: 90%; height: auto; max-height: 75%; }');
            printWindow.document.write('.barcode-text { font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; margin-top: 1px; letter-spacing: 1px; }');
            printWindow.document.write('.page-break { page-break-after: always; break-after: page; }');
            printWindow.document.write('@page { size: ' + width + ' ' + height + ' ' + orientation + '; margin: 0; }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContents);
            printWindow.document.write('</body></html>');

            printWindow.document.close();
            printWindow.focus();

            setTimeout(function () {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
    </script>
</div>