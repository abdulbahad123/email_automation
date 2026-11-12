@extends('layouts.app')

@section('sub_header')
    <x-sub-header 
        title="{{ __('WhatsApp Unofficial API Configuration') }}" 
        description="{{ __('Configure WhatsApp Unofficial API server connection') }}" 
    >
    </x-sub-header>
@endsection

@section('content')

<div class="container max-w-800 pb-5">

    <form class="actionForm" action="{{ url_admin('settings/save') }}">

        <div class="card shadow-none border-gray-300 mb-4">

            <div class="card-header">
                <div class="fw-6">
                    <i class="fab fa-whatsapp text-success me-2"></i>
                    {{ __("WhatsApp API Configuration") }}
                </div>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-12">
                        <div class="mb-4">
                            <label class="form-label">{{ __('Status') }}</label>
                            <div class="d-flex gap-8 flex-column flex-lg-row flex-md-column">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="whatsapp_unofficial_profile_status" value="1" id="whatsapp_unofficial_profile_status_1" {{ get_option("whatsapp_unofficial_profile_status", 0)==1?"checked":"" }}>
                                    <label class="form-check-label mt-1" for="whatsapp_unofficial_profile_status_1">
                                        {{ __('Enable') }}
                                    </label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="whatsapp_unofficial_profile_status" value="0" id="whatsapp_unofficial_profile_status_0"{{ get_option("whatsapp_unofficial_profile_status", 0)==0?"checked":"" }}>
                                    <label class="form-check-label mt-1" for="whatsapp_unofficial_profile_status_0">
                                        {{ __('Disable') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- WhatsApp Server URL --}}
                    <div class="col-md-12">

                        <div class="mb-4">

                            <label class="form-label">
                                {{ __('WhatsApp Server URL') }}
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="whatsapp_server_url"
                                placeholder="https://example.com/"
                                value="{{ get_option('whatsapp_server_url','') }}"
                            >

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Save button --}}
        <div class="mt-4">

            <button type="submit" class="btn btn-primary b-r-10">
                {{ __('Save') }}
            </button>

        </div>

    </form>

</div>

@endsection