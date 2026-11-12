<div class="card b-r-6 border-gray-300 mb-3 wa-unofficial-permissions">
    <div class="card-header">
        <div class="form-check">
            <input class="form-check-input prevent-toggle wa-unofficial-master" type="checkbox" value="1" id="permissions[appchannelwhatsappunofficial]" name="permissions[appchannelwhatsappunofficial]" @checked(array_key_exists('appchannelwhatsappunofficial', $permissions))>
            <label class="fw-6 fs-14 text-gray-700 ms-2" for="permissions[appchannelwhatsappunofficial]">
                {{ __('WhatsApp Unofficial') }}
            </label>
        </div>
        <input class="form-control d-none" name="labels[appchannelwhatsappunofficial]" type="text" value="WhatsApp Unofficial">
    </div>
    <div class="card-body wa-unofficial-body">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-4">
                    <div class="fw-5 text-gray-800 fs-14 mb-2">{{ __('Features') }}</div>
                    <div class="d-flex flex-wrap gap-8">
                        @php
                            $featurePermissions = [
                                'appwhatsappprofileinfo' => 'Profile Info',
                                'appwhatsappreport' => 'Reports',
                                'appwhatsappchat' => 'Live Chat',
                                'appwhatsappbulk' => 'Bulk campaigns',
                                'appwhatsappaismartreply' => 'AI Smart Reply',
                                'appwhatsappautoreply' => 'Auto Reply',
                                'appwhatsappchatbot' => 'Chatbot',
                                'appwhatsappcontact' => 'Contacts',
                                'appwhatsappparticipantsexport' => 'Export participants',
                                'appwhatsappapi' => 'REST API',
                            ];
                        @endphp

                        @foreach($featurePermissions as $key => $label)
                            <div class="mb-2">
                                <div class="form-check me-3">
                                    <input class="form-check-input checkbox-item" type="checkbox" name="permissions[{{ $key }}]" value="1" id="{{ $key }}" @checked(array_key_exists($key, $permissions))>
                                    <label class="form-check-label mt-1 text-truncate" for="{{ $key }}">
                                        {{ __($label) }}
                                    </label>
                                </div>
                                <input class="form-control d-none" name="labels[{{ $key }}]" type="text" value="{{ $label }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-4">
                    <label for="whatsapp_chatbot_item_limit" class="form-label">{{ __('Chatbot item limit per account') }}</label>
                    <input class="form-control" name="permissions[whatsapp_chatbot_item_limit]" id="whatsapp_chatbot_item_limit" type="number" value="{{ $permissions['whatsapp_chatbot_item_limit'] ?? '5' }}">
                    <input class="form-control d-none" name="labels[whatsapp_chatbot_item_limit]" type="text" value="Chatbot item limit per account">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-4">
                    <label for="whatsapp_bulk_max_contact_group" class="form-label">{{ __('Maximum contact groups') }}</label>
                    <input class="form-control" name="permissions[whatsapp_bulk_max_contact_group]" id="whatsapp_bulk_max_contact_group" type="number" value="{{ $permissions['whatsapp_bulk_max_contact_group'] ?? '5' }}">
                    <input class="form-control d-none" name="labels[whatsapp_bulk_max_contact_group]" type="text" value="Maximum contact groups">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-4">
                    <label for="whatsapp_bulk_max_phone_numbers" class="form-label">{{ __('Maximum phone numbers per contact group') }}</label>
                    <input class="form-control" name="permissions[whatsapp_bulk_max_phone_numbers]" id="whatsapp_bulk_max_phone_numbers" type="number" value="{{ $permissions['whatsapp_bulk_max_phone_numbers'] ?? '100' }}">
                    <input class="form-control d-none" name="labels[whatsapp_bulk_max_phone_numbers]" type="text" value="Maximum phone numbers per contact group">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-4">
                    <label for="whatsapp_message_per_month" class="form-label">{{ __('Monthly WhatsApp message limit') }}</label>
                    <input class="form-control" name="permissions[whatsapp_message_per_month]" id="whatsapp_message_per_month" type="number" value="{{ $permissions['whatsapp_message_per_month'] ?? '100' }}">
                    <div class="fs-12 text-gray-500 mt-1">{{ __('Set `-1` for unlimited monthly messages.') }}</div>
                    <input class="form-control d-none" name="labels[whatsapp_message_per_month]" type="text" value="Monthly WhatsApp message limit">
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function () {
        var selector = '.wa-unofficial-permissions';

        function syncWhatsAppUnofficialPermissions(container) {
            var card = $(container);
            var enabled = card.find('.wa-unofficial-master').is(':checked');
            var body = card.find('.wa-unofficial-body');
            var childChecks = body.find('input[type="checkbox"]').not('.wa-unofficial-master');
            var inputs = body.find('input[type="number"], input[type="text"], textarea, select');

            body.toggleClass('opacity-50', !enabled);
            inputs.prop('disabled', !enabled);

            if (!enabled) {
                childChecks.prop('checked', false);
            }
        }

        $(document).off('change.waUnofficialPermissions').on('change.waUnofficialPermissions', '.wa-unofficial-master', function () {
            syncWhatsAppUnofficialPermissions($(this).closest(selector));
        });

        $(function () {
            $(selector).each(function () {
                syncWhatsAppUnofficialPermissions(this);
            });
        });
    })();
</script>
