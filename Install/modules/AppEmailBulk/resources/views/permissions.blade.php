<div class="card b-r-6 border-gray-300 mb-3">
    <div class="card-header">
        <div class="form-check">
            <input class="form-check-input email-automation-master-toggle" type="checkbox" value="1" id="permissions[appemailautomation]" name="permissions[appemailautomation]" @checked(array_key_exists('appemailautomation', $permissions))>
            <label class="form-check-label fw-6 fs-14 text-gray-800" for="permissions[appemailautomation]">
                {{ __('Email Automation') }}
            </label>
        </div>
        <input class="form-control d-none" name="labels[appemailautomation]" type="text" value="Email Automation">
    </div>
    <div class="card-body email-automation-permissions">
        <div class="fw-5 fs-12 mb-2 text-gray-800">{{ __('Features') }}</div>
        <div class="row">
            @php
                $emailFeatures = [
                    ['key' => 'appemailbulk', 'label' => __('Bulk Email')],
                    ['key' => 'appemailsend', 'label' => __('Send Single Email')],
                    ['key' => 'appemailcontact', 'label' => __('Email Contacts')],
                    ['key' => 'appemailsmtp', 'label' => __('SMTP Accounts')],
                ];
            @endphp
            @foreach($emailFeatures as $feature)
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input email-automation-feature" type="checkbox" value="1" id="permissions[{{ $feature['key'] }}]" name="permissions[{{ $feature['key'] }}]" @checked(array_key_exists($feature['key'], $permissions))>
                        <label class="form-check-label" for="permissions[{{ $feature['key'] }}]">
                            {{ $feature['label'] }}
                        </label>
                    </div>
                    <input class="form-control d-none" name="labels[{{ $feature['key'] }}]" type="text" value="{{ $feature['label'] }}">
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    (function () {
        function syncEmailAutomationPermissions(autoCheckOnEnable) {
            var $master = $('.email-automation-master-toggle');
            var $body = $('.email-automation-permissions');
            if (!$master.length || !$body.length) return;

            var enabled = $master.is(':checked');
            $body.css('opacity', enabled ? '1' : '0.45');
            $body.find('input').prop('disabled', !enabled);

            if (enabled && autoCheckOnEnable) {
                var $features = $body.find('.email-automation-feature');
                if ($features.filter(':checked').length === 0) {
                    $features.prop('checked', true);
                }
            }
        }

        $(document).ready(function () {
            syncEmailAutomationPermissions(false);
            $(document).on('change', '.email-automation-master-toggle', function () {
                syncEmailAutomationPermissions(true);
            });
        });
    })();
</script>
