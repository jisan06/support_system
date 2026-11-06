@extends('admin.layouts.master')
@section('title', trans_choice('menu.settings', 1) . ' - ' . __(config("settings.{$group}.label")))
@section('content')
<div class="row">
    <div class="col-12">
        {{-- Settings Form --}}
        <form action="{{ route('settings.update', $group) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{ __( config("settings.{$group}.label") ) }}
                    </h5>
                </div>
                <div class="card-body">
                    @include('components.message.error')

                    {{-- Image fields grouped together --}}
                    @php
                        $imageFields = collect($fields)->where('type', 'image');
                        $otherFields = collect($fields)->where('type', '!=', 'image');
                    @endphp

                    @if($imageFields->isNotEmpty())
                        <div class="card border shadow-sm mb-3">
                            <div class="card-header py-2">
                                <h6 class="mb-0"><i class="bx bx-image me-1"></i> @lang('setting.branding')</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($imageFields as $key => $field)
                                        @php
                                            $mediaId = $values[$key] ?? null;
                                            $mediaUrl = null;
                                            if ($mediaId) {
                                                $media = \App\Models\Media::find($mediaId);
                                                $mediaUrl = $media ? \Illuminate\Support\Facades\Storage::disk($media->disk)->url($media->path) : null;
                                            }
                                        @endphp
                                        <div class="col-sm-{{ $field['col'] ?? 4 }} mb-3">
                                            <div class="form-group text-center">
                                                <label class="form-label d-block fw-medium">{{ __($field['label']) }}</label>
                                                <div class="setting-image-preview mx-auto" id="preview_{{ $key }}" title="{{ __('setting.click_to_change') }}">
                                                    @if($mediaUrl)
                                                        <img src="{{ $mediaUrl }}" alt="{{ $key }}">
                                                    @else
                                                        <i class="bx bx-image" style="font-size:40px; color:#adb5bd;"></i>
                                                    @endif
                                                </div>
                                                <input type="hidden" name="{{ $key }}" id="input_{{ $key }}" value="{{ $mediaId }}">
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm image-pick-btn" data-field="{{ $key }}">
                                                        <i class="bx bx-image-add me-1"></i> {{ __('setting.choose_image') }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm image-remove-btn {{ $mediaUrl ? '' : 'd-none' }}" data-field="{{ $key }}">
                                                        <i class="bx bx-trash me-1"></i> {{ __('setting.remove') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Other fields --}}
                    @if($otherFields->isNotEmpty())
                        <div class="row">
                            @foreach($otherFields as $key => $field)
                                <div class="col-sm-{{ $field['col'] ?? 6 }} mb-3">
                                    @switch($field['type'])
                                        @case('text')
                                            <x-form.input-text
                                                :name="$key"
                                                :value="$values[$key] ?? ''"
                                                :label="__($field['label'])"
                                                :placeholder="__($field['placeholder'] ?? '')"
                                                :required="false"
                                            />
                                            @break

                                        @case('email')
                                            <x-form.input-email
                                                :name="$key"
                                                :value="$values[$key] ?? ''"
                                                :label="__($field['label'])"
                                                :placeholder="__($field['placeholder'] ?? '')"
                                                :required="false"
                                            />
                                            @break

                                        @case('textarea')
                                            <x-form.textarea
                                                :name="$key"
                                                :value="$values[$key] ?? ''"
                                                :label="__($field['label'])"
                                                :placeholder="__($field['placeholder'] ?? '')"
                                            />
                                            @break

                                        @case('select')
                                            @php
                                                $selectLabel = __($field['label']);
                                                if ($key === 'default_language') {
                                                    $langFlags = ['en' => 'us.jpg', 'bn' => 'bd.png', 'ar' => 'sa.png', 'es' => 'es.png'];
                                                    $currentLang = $values[$key] ?? 'en';
                                                    $flagFile = $langFlags[$currentLang] ?? 'us.jpg';
                                                    $flagImg = '<img src="' . admin_asset('images/flags/' . $flagFile) . '" height="14" style="vertical-align:middle;" id="lang-flag-icon">';
                                                    $selectLabel = '(<i class="mdi mdi-translate" style="font-size:18px;vertical-align:middle;"></i>) ' . $selectLabel . ' ' . $flagImg;
                                                }
                                            @endphp
                                            <x-form.select
                                                :name="$key"
                                                :label="$selectLabel"
                                                :options="$field['options'] ?? []"
                                                :selected="$values[$key] ?? ($field['default'] ?? '')"
                                                :select2="count($field['options'] ?? []) > 20"
                                                :multiple="false"
                                            />
                                            @break
                                    @endswitch
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-times" aria-hidden="true"></i>
                        @lang('common.cancel')
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        @lang('common.submit_button')
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(collect($fields)->where('type', 'image')->isNotEmpty())
    @include('components.media.picker-modal')
@endif
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
    @vite(['resources/css/media-picker.css'])
    <style>
        .setting-image-preview {
            height: 120px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            transition: border-color 0.2s;
            background: #f8f9fa;
            padding: 15px;
        }
        .setting-image-preview:hover {
            border-color: #556ee6;
        }
        .setting-image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
@endpush

@push('script_links')
    <script src="{{ asset('backend/assets/js/media-picker.js') }}"></script>
    <script>
    $(function() {
        $('.image-pick-btn').on('click', function() {
            var field = $(this).data('field');
            MediaPicker.open(function(media) {
                $('#input_' + field).val(media.id);
                $('#preview_' + field).html('<img src="' + media.url + '" alt="' + field + '">');
                $('.image-remove-btn[data-field="' + field + '"]').removeClass('d-none');
            });
        });

        $('.setting-image-preview').on('click', function() {
            var field = $(this).attr('id').replace('preview_', '');
            $('.image-pick-btn[data-field="' + field + '"]').trigger('click');
        });

        $('.image-remove-btn').on('click', function() {
            var field = $(this).data('field');
            $('#input_' + field).val('');
            $('#preview_' + field).html('<i class="bx bx-image" style="font-size:40px; color:#adb5bd;"></i>');
            $(this).addClass('d-none');
        });

        var langFlags = {en:'us.jpg', bn:'bd.png', ar:'sa.png', es:'es.png'};
        $('#default_language').on('change', function() {
            var flag = langFlags[$(this).val()] || 'us.jpg';
            $('#lang-flag-icon').attr('src', '{{ admin_asset("images/flags") }}/' + flag);
        });
    });
    </script>
@endpush

@include('admin.layouts.include.others.select2')
