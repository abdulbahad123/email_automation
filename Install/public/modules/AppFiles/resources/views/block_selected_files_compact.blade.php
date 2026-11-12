<div class="file-selected-media compact-media-picker" id="medias">
    <div class="items clearfix ui-sortable">
        @if(!empty($files))
            @php
                $results = DB::table('files')->whereIn('file', $files)->get();
            @endphp

            @foreach($results as $value)
                @php
                    $detectType = Media::detectFileIcon($value->detect);
                @endphp

                <div class="file-item ratio ratio-1x1 border rounded selected text-{{ $detectType['color'] }} bg-{{ $detectType['color'] }}-100 position-relative"
                     data-id="file_{{ $value->id_secure }}"
                     data-file="{{ Media::url($value->file) }}"
                     data-type="{{ $value->detect }}">
                    <label class="d-flex flex-column flex-fill m-0">
                        <div class="position-absolute r-6 t-6 zIndex-1">
                            <div class="form-check form-check-sm">
                                <input class="form-check-input" name="medias[]" type="hidden" value="{{ $value->file }}" id="file_{{ $value->id_secure }}">
                            </div>
                        </div>
                        <div class="d-flex flex-fill align-items-center justify-content-center overflow-hidden position-relative file-item-media"
                             @if($value->detect == 'image') style="background-image:url('{{ Media::url($value->file) }}');" @endif>
                            @if($value->detect != 'image')
                                <div class="fs-24">
                                    <i class="{{ $detectType['icon'] }}"></i>
                                </div>
                            @endif
                        </div>
                    </label>
                    <button type="button" class="remove rounded-circle position-absolute" title="{{ __('Remove file') }}">
                        <i class="fa-light fa-xmark fs-10"></i>
                    </button>
                </div>
            @endforeach
        @endif
    </div>

    <div class="drophere text-center" style="{{ $files ? 'display: none;' : '' }}">
        <span class="has-action d-none">{{ __('Drop here') }}</span>
        <span class="no-action">{{ __('Choose one image, video, or file from Add files.') }}</span>
    </div>
</div>
