<div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <form method="post" wire:submit="{{ $this->modalTitle === "New" ? 'save()' : 'update('.$this->modelId.',)' }}">
        <div class="modal-dialog modal-dialog-centered" role="document">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $this->modalTitle }} {{ $this->modalName }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">

                        <div class="col-12">
                            @foreach($this->data as $key=>$value)
                                @if($value['type'] === "text" || $value['type'] === "email")
                                    <div class="mb-3">
                                        <label class="form-label">{{ $value['label'] }}</label>
                                        <input class="form-control" type="{{ $value['type'] }}" wire:model="formData.{{ $key }}"  name="{{ $key }}"  placeholder="{{ $value['label'] }}">
                                        @error("formData.$key") <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                @if($value['type'] === "password")
                                    <div class="mb-3">
                                        <label class="form-label">{{ $value['label'] }}</label>
                                        <input class="form-control" type="{{ $value['type'] }}" wire:model="formData.{{ $key }}"  name="{{ $key }}"  placeholder="{{ $value['label'] }}">
                                        @if($this->modalTitle == "Update")
                                            <span class="text-green d-block">Leave Blank if you don't want to change password</span>
                                        @endif
                                        @error("formData.$key") <span class="text-danger d-block">{{ $message }}</span> @enderror
                                    </div>
                                @endif
                                @if($value['type'] === "select")
                                    <div class="mb-3">
                                        <label class="form-label">{{ $value['label'] }}</label>
                                        <select class="form-control" @isset($value['change']) wire:change="{{ $value['change'] }}"  @endisset name="{{ $key }}" @if(isset($value['change'])) wire:model=  @else wire:model=  @endif "formData.{{ $key }}">
                                        <option value="">Select {{ $value['label'] }}</option>
                                        @foreach($value['options'] as $option)
                                            @php
                                                if(is_object($option)){
                                                    $option = (array)$option;
                                                }
                                            @endphp
                                            <option value="{{ (String) $option['id']  }}">{{  $option[(isset($value['label_option']) ? $value['label_option'] : 'name' )] }}</option>
                                            @endforeach
                                            </select>
                                            @error("formData.$key") <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                @if($value['type'] === "textarea")
                                    <div class="mb-3">
                                        <label class="form-label">{{ $value['label'] }}</label>
                                        <textarea class="form-control" name="{{ $key }}"  wire:model="formData.{{ $key }}"  placeholder="{{ $value['label'] }}"></textarea>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="submit" wire:target="save,update" wire:loading.attr="disabled" class="btn btn-primary">
                        <span wire:loading wire:target="save,update" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        {{ $this->saveButton }}
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>

        </div>
    </form>
</div>


<script>
    window.onload = function (){
        let myModal = "";
        $(document).ready(function(){
            myModal = new bootstrap.Modal(document.getElementById("simpleComponentModal"), {});
        });
        window.addEventListener('openModal', (e) => {
            myModal.show();
        });
        window.addEventListener('closeModal', (e) => {
            myModal.hide();
        });
    }
</script>
