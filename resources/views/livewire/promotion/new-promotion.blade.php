<div>

    <form method="post" enctype="multipart/form-data" wire:submit="store">
        <div class="row">
            <div class="col-7 offset-3">
                <div class="mb-3">
                    <label>Promotion Name</label>
                    <input class="form-control" wire:model="name" required placeholder="Promotion Name"  name="name" value=""/>
                    @if ($errors->has('name'))
                        <label for="name-error" class="error"
                               style="display: inline-block;">{{ $errors->first('name') }}</label>
                    @endif
                </div>
                <div class="mb-3">
                    <label>Promo Runs From</label>
                    <input class="form-control datepicker-basic" wire:model="from" required name="from" />
                    @if ($errors->has('from'))
                        <label for="name-error" class="error"
                               style="display: inline-block;">{{ $errors->first('from') }}</label>
                    @endif
                </div>
                <div class="mb-3">
                    <label>Promo Runs To</label>
                    <input class="form-control datepicker-basic" wire:model="to" required name="to" />
                    @if ($errors->has('to'))
                        <label for="name-error" class="error"
                               style="display: inline-block;">{{ $errors->first('to') }}</label>
                    @endif
                </div>
                <div class="mb-3">
                    <label>Upload Promo Stock(s)</label>
                    <input type="file" class="form-control datepicker" wire:model="template" required name="template"/>
                    <a href="{{ asset('templates/promotion-stock-template.xlsx') }}">Download Template</a>
                    @if ($errors->has('to'))
                        <label for="name-error" class="error"
                               style="display: inline-block;">{{ $errors->first('to') }}</label>
                    @endif
                </div>

                <div class="col-lg-12 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg me-2" wire:loading.attr="disabled">Save

                        <i wire:loading.remove wire:target="store" class="fa fa-check"></i>

                        <span wire:loading wire:target="store" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    </button>
                    <a href="{{ route('promo.index') }}" class="btn btn-danger btn-lg">Cancel</a>
                </div>
            </div>
        </div>
    </form>


</div>
