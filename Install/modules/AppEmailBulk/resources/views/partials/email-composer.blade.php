@php
    $prefix = $prefix ?? 'email';
    $formId = $formId ?? 'EmailForm';
    $subject = $subject ?? '';
    $body = $body ?? '';
    $existingAttachment = $existingAttachment ?? '';
    $templates = $templates ?? [];
    $isBulk = $isBulk ?? false;
    $uploadImageUrl = route('app.emailbulk.upload_image');
@endphp

<div class="email-composer-wrapper" data-prefix="{{ $prefix }}" data-form-id="{{ $formId }}" data-is-bulk="{{ $isBulk ? '1' : '0' }}">
    <div class="mb-3">
        <label class="form-label fw-6">{{ __('Email Subject') }} <span class="text-danger">*</span></label>
        <input type="text"
               id="{{ $prefix }}_email_subject"
               class="form-control"
               name="subject"
               value="{{ $subject }}"
               placeholder="{{ $isBulk ? __('Example: {Hi|Hello} [name], special offer inside') : __('Enter email subject') }}"
               required>
    </div>

    <!-- Composer Mode Tabs: Custom Message vs Use Default Format -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-8 mb-2">
        <label class="form-label fw-6 mb-0">{{ __('Email Format & Body') }} <span class="text-danger">*</span></label>
        <ul class="nav nav-pills bg-light p-1 rounded-3 border" id="{{ $prefix }}_mode_tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-1 px-3 fs-12 fw-6"
                        id="{{ $prefix }}_tab_custom_btn"
                        data-bs-toggle="pill"
                        data-bs-target="#{{ $prefix }}_tab_custom"
                        type="button"
                        role="tab">
                    <i class="fa-light fa-pen-to-square me-1"></i>{{ __('Custom Message') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 px-3 fs-12 fw-6"
                        id="{{ $prefix }}_tab_template_btn"
                        data-bs-toggle="pill"
                        data-bs-target="#{{ $prefix }}_tab_template"
                        type="button"
                        role="tab">
                    <i class="fa-light fa-sparkles me-1"></i>{{ __('Use Default Format (Templates)') }}
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content mb-3">
        <!-- TAB 1: Custom Message (Don't use default format) -->
        <div class="tab-pane fade show active" id="{{ $prefix }}_tab_custom" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-8 mb-2">
                <div class="d-flex flex-wrap align-items-center gap-6">
                    @if($isBulk)
                        <span class="fs-11 text-gray-500 me-1">{{ __('Variables:') }}</span>
                        <button type="button" class="btn btn-light btn-sm insert-email-var" data-target="{{ $prefix }}_email_body" data-var="[name]">[name]</button>
                        <button type="button" class="btn btn-light btn-sm insert-email-var" data-target="{{ $prefix }}_email_body" data-var="[email]">[email]</button>
                        <button type="button" class="btn btn-light btn-sm insert-email-var" data-target="{{ $prefix }}_email_body" data-var="[company]">[company]</button>
                        <button type="button" class="btn btn-light btn-sm insert-email-var" data-target="{{ $prefix }}_email_body" data-var="[unsubscribe_url]">[unsubscribe_url]</button>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-8">
                    <label class="btn btn-outline btn-primary btn-sm mb-0 cursor-pointer" for="{{ $prefix }}_inline_upload_custom">
                        <i class="fa-light fa-image me-1"></i>{{ __('Insert Image in Body') }}
                    </label>
                    <input type="file"
                           id="{{ $prefix }}_inline_upload_custom"
                           class="d-none email-inline-upload"
                           data-target="{{ $prefix }}"
                           data-mode="custom"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
            </div>

            <div id="{{ $prefix }}_inline_upload_status" class="fs-12 text-primary mb-2 d-none"></div>

            <textarea id="{{ $prefix }}_email_body"
                      class="email-tinymce-editor form-control min-h-260"
                      name="body"
                      placeholder="{{ __('Write your HTML email message here...') }}">{{ $body }}</textarea>

            @if($isBulk)
                <div class="fs-12 text-gray-500 mt-1">
                    {{ __('Supports Spintax ({Hi|Hello|Hey}) and CSV columns like [name], [company], [city]. Images inserted here display inline inside the email.') }}
                </div>
            @else
                <div class="fs-12 text-gray-500 mt-1">
                    {{ __('Use "Insert Image in Body" or paste/drag an image to show it directly inside the email.') }}
                </div>
            @endif
        </div>

        <!-- TAB 2: Default Marketing Formats -->
        <div class="tab-pane fade" id="{{ $prefix }}_tab_template" role="tabpanel">
            <div class="border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-8 mb-3">
                    <div>
                        <div class="fw-6 fs-14 text-gray-900">{{ __('Default Marketing Email Formats') }}</div>
                        <div class="fs-12 text-gray-600">{{ __('Select any preset below and customize your headline, banner image, message, and CTA button.') }}</div>
                    </div>
                    <button type="button" class="btn btn-light btn-sm border tpl-reset-custom-btn" data-prefix="{{ $prefix }}">
                        <i class="fa-light fa-rotate-left me-1"></i>{{ __('Do Not Use Format') }}
                    </button>
                </div>

                <!-- Preset Selector Cards (never use .btn here because theme .btn enforces fixed height & nowrap) -->
                <div class="row g-2 mb-3">
                    @foreach($templates as $idx => $tpl)
                        <div class="col-12 col-sm-6">
                            <div role="button"
                                 tabindex="0"
                                 class="p-3 border rounded-3 bg-white h-100 tpl-preset-card {{ $idx === 0 ? 'border-primary shadow-sm' : 'border-gray-200' }}"
                                 style="cursor: pointer; white-space: normal; display: block;"
                                 data-prefix="{{ $prefix }}"
                                 data-template-id="{{ $tpl['id'] }}">
                                <div class="d-flex align-items-center gap-8 mb-1">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                                          style="width: 24px; height: 24px; background: {{ $tpl['accent'] }}; color: #fff; font-size: 12px;">
                                        <i class="{{ $tpl['icon'] }}"></i>
                                    </span>
                                    <span class="fw-6 fs-13 text-gray-900">{{ $tpl['name'] }}</span>
                                </div>
                                <div class="fs-12 text-gray-500" style="line-height: 1.4; white-space: normal;">{{ $tpl['description'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Template Customization Fields (Full width inside composer) -->
                <div class="bg-white border rounded-3 p-3 mb-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Brand / Company Name') }}</label>
                            <input type="text" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_brand" data-prefix="{{ $prefix }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Top Badge') }}</label>
                            <input type="text" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_badge" data-prefix="{{ $prefix }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Accent Color') }}</label>
                            <input type="color" class="form-control form-control-color form-control-sm w-100 tpl-field" id="{{ $prefix }}_tpl_accent" value="#4f46e5" data-prefix="{{ $prefix }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Headline') }}</label>
                            <input type="text" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_headline" data-prefix="{{ $prefix }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Header / Banner Image (Optional)') }}</label>
                            <div class="d-flex flex-wrap gap-8 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-grow-1 tpl-field" style="min-width: 180px; width: auto;" id="{{ $prefix }}_tpl_image_url" placeholder="{{ __('Upload an image or paste https:// image URL') }}" data-prefix="{{ $prefix }}">
                                <label class="btn btn-primary btn-sm mb-0 cursor-pointer flex-shrink-0" for="{{ $prefix }}_inline_upload_tpl">
                                    <i class="fa-light fa-upload me-1"></i>{{ __('Upload') }}
                                </label>
                                <button type="button" class="btn btn-light btn-sm border tpl-clear-image flex-shrink-0" data-prefix="{{ $prefix }}" title="{{ __('Remove image') }}">
                                    <i class="fa-light fa-xmark"></i>
                                </button>
                            </div>
                            <input type="file"
                                   id="{{ $prefix }}_inline_upload_tpl"
                                   class="d-none email-inline-upload"
                                   data-target="{{ $prefix }}"
                                   data-mode="template"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>

                        <div class="col-12">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Main Message') }}</label>
                            <textarea rows="5" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_message" data-prefix="{{ $prefix }}"></textarea>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Button Text') }}</label>
                            <input type="text" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_cta_text" placeholder="{{ __('Leave empty to hide') }}" data-prefix="{{ $prefix }}">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Button Link (URL)') }}</label>
                            <input type="text" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_cta_url" placeholder="https://..." data-prefix="{{ $prefix }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fs-12 fw-6 mb-1">{{ __('Footer Note') }}</label>
                            <input type="text" class="form-control form-control-sm tpl-field" id="{{ $prefix }}_tpl_footer" data-prefix="{{ $prefix }}">
                        </div>
                    </div>
                </div>

                <!-- Live Email Preview (Full width) -->
                <div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-8 mb-2">
                        <span class="fs-12 fw-6 text-gray-700">{{ __('Live Email Preview') }}</span>
                        <button type="button" class="btn btn-primary btn-sm tpl-apply-to-editor" data-prefix="{{ $prefix }}">
                            <i class="fa-light fa-check me-1"></i>{{ __('Apply & Edit in HTML') }}
                        </button>
                    </div>
                    <div id="{{ $prefix }}_tpl_preview"
                         class="border rounded-3 bg-white p-2 overflow-auto"
                         style="max-height: 420px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Separate Body Image Fallback & Downloadable File Attachment -->
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label fw-6">{{ __('Quick Body Image (Optional)') }}</label>
            <input type="file" class="form-control" name="inline_image" accept="image/jpeg,image/png,image/gif,image/webp">
            <div class="fs-11 text-gray-500 mt-1">{{ __('Shown inside the email message body (auto-embedded for Gmail/Outlook).') }}</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label fw-6">{{ __('File Attachment (Optional)') }}</label>
            <input type="file" class="form-control" name="attachment">
            @if(!empty($existingAttachment))
                <div class="d-flex align-items-center gap-12 mt-2 fs-12 text-gray-700">
                    <span><i class="fa-light fa-paperclip me-1"></i>{{ basename($existingAttachment) }}</span>
                    <label class="form-check-label text-danger cursor-pointer">
                        <input type="checkbox" class="form-check-input me-1" name="remove_attachment" value="1">{{ __('Remove current attachment') }}
                    </label>
                </div>
            @else
                <div class="fs-11 text-gray-500 mt-1">{{ __('Sent as a downloadable attachment (PDF, brochure, PNG, DOCX, ZIP).') }}</div>
            @endif
        </div>
    </div>
</div>

@once
<script type="text/javascript">
(function () {
    window.EmailComposerState = window.EmailComposerState || {};
    var TEMPLATES = @json($templates);
    var UPLOAD_URL = @json($uploadImageUrl);
    var CSRF_TOKEN = @json(csrf_token());

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function findTemplate(id) {
        for (var i = 0; i < TEMPLATES.length; i++) {
            if (TEMPLATES[i].id === id) return TEMPLATES[i];
        }
        return TEMPLATES[0] || null;
    }

    function uploadEmailImageFile(file, onSuccess, onError) {
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', CSRF_TOKEN);

        fetch(UPLOAD_URL, {
            method: 'POST',
            body: fd,
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.location) {
                onSuccess(res.location);
            } else {
                onError((res && res.message) ? res.message : 'Image upload failed');
            }
        })
        .catch(function (err) {
            onError(err && err.message ? err.message : 'Image upload failed');
        });
    }

    function initEmailTinyMCE(prefix) {
        var textareaId = prefix + '_email_body';
        var el = document.getElementById(textareaId);
        if (!el || !window.tinymce || tinymce.get(textareaId)) return;

        tinymce.init({
            selector: '#' + textareaId,
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak code',
            toolbar: 'undo redo | blocks | bold italic underline forecolor | alignleft aligncenter alignright | bullist numlist | link image | removeformat code',
            toolbar_mode: 'floating',
            menubar: false,
            statusbar: false,
            min_height: 260,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: false,
            automatic_uploads: true,
            paste_data_images: true,
            images_upload_handler: function (blobInfo) {
                return new Promise(function (resolve, reject) {
                    uploadEmailImageFile(blobInfo.blob(), resolve, reject);
                });
            },
            file_picker_types: 'image',
            file_picker_callback: function (cb) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
                input.onchange = function () {
                    var file = this.files && this.files[0];
                    if (!file) return;
                    uploadEmailImageFile(
                        file,
                        function (url) { cb(url, { alt: file.name || 'Email Image' }); },
                        function (msg) { alert(msg); }
                    );
                };
                input.click();
            },
            setup: function (editor) {
                editor.on('change keyup input SetContent NodeChange', function () {
                    editor.save();
                });
            }
        });
    }

    function setEditorContent(prefix, html) {
        var textareaId = prefix + '_email_body';
        var el = document.getElementById(textareaId);
        if (el) {
            el.value = html;
        }
        if (window.tinymce && tinymce.get(textareaId)) {
            tinymce.get(textareaId).setContent(html);
            tinymce.get(textareaId).save();
        }
    }

    function buildTemplateHtml(prefix) {
        var val = function (suffix) {
            var el = document.getElementById(prefix + suffix);
            return el ? el.value.trim() : '';
        };

        var brand = val('_tpl_brand') || 'Our Company';
        var badge = val('_tpl_badge');
        var accent = val('_tpl_accent') || '#4f46e5';
        var headline = val('_tpl_headline');
        var imageUrl = val('_tpl_image_url');
        var rawMsg = val('_tpl_message');
        var ctaText = val('_tpl_cta_text');
        var ctaUrl = val('_tpl_cta_url') || '#';
        var footer = val('_tpl_footer');

        var messageHtml = escapeHtml(rawMsg).replace(/\r?\n/g, '<br>');

        var badgeHtml = badge
            ? '<div style="margin-bottom:12px;"><span style="display:inline-block;background:' + escapeHtml(accent) + ';color:#ffffff;font-size:11px;font-weight:700;letter-spacing:0.06em;padding:5px 12px;border-radius:999px;text-transform:uppercase;">' + escapeHtml(badge) + '</span></div>'
            : '';

        var bannerHtml = imageUrl
            ? '<div style="margin:0 0 20px 0;text-align:center;"><img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(headline || brand) + '" style="width:100%;max-width:560px;height:auto;border-radius:10px;display:block;margin:0 auto;"></div>'
            : '';

        var headlineHtml = headline
            ? '<h1 style="margin:0 0 14px 0;font-size:22px;line-height:1.3;color:#0f172a;font-weight:700;">' + escapeHtml(headline) + '</h1>'
            : '';

        var ctaHtml = ctaText
            ? '<div style="margin:24px 0 8px 0;text-align:center;"><a href="' + escapeHtml(ctaUrl) + '" target="_blank" style="display:inline-block;background:' + escapeHtml(accent) + ';color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 26px;border-radius:8px;">' + escapeHtml(ctaText) + '</a></div>'
            : '';

        return ''
            + '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;">'
            + '<tr><td align="center">'
            + '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">'
            + '<tr><td style="height:5px;background:' + escapeHtml(accent) + ';font-size:0;line-height:0;">&nbsp;</td></tr>'
            + '<tr><td style="padding:20px 28px 14px 28px;border-bottom:1px solid #f1f5f9;">'
            + '<div style="font-size:16px;font-weight:700;color:#0f172a;">' + escapeHtml(brand) + '</div>'
            + '</td></tr>'
            + '<tr><td style="padding:24px 28px;">'
            + badgeHtml
            + bannerHtml
            + headlineHtml
            + '<div style="font-size:15px;line-height:1.68;color:#334155;">' + messageHtml + '</div>'
            + ctaHtml
            + '</td></tr>'
            + (footer ? '<tr><td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;text-align:center;">' + escapeHtml(footer) + '</td></tr>' : '')
            + '</table>'
            + '</td></tr></table>';
    }

    function renderTemplatePreviewAndSync(prefix, shouldSyncEditor) {
        var html = buildTemplateHtml(prefix);
        var previewEl = document.getElementById(prefix + '_tpl_preview');
        if (previewEl) {
            previewEl.innerHTML = html;
        }
        if (shouldSyncEditor) {
            setEditorContent(prefix, html);
        }
        return html;
    }

    function loadTemplateIntoFields(prefix, templateId, syncEditorAndSubject) {
        var tpl = findTemplate(templateId);
        if (!tpl) return;

        window.EmailComposerState[prefix] = window.EmailComposerState[prefix] || {};
        window.EmailComposerState[prefix].selectedTemplateId = tpl.id;

        var setVal = function (suffix, val) {
            var input = document.getElementById(prefix + suffix);
            if (input) input.value = val || '';
        };

        setVal('_tpl_brand', tpl.default_brand);
        setVal('_tpl_badge', tpl.default_badge);
        setVal('_tpl_accent', tpl.accent || '#4f46e5');
        setVal('_tpl_headline', tpl.default_headline);
        setVal('_tpl_message', tpl.default_body);
        setVal('_tpl_cta_text', tpl.default_cta_text);
        setVal('_tpl_cta_url', tpl.default_cta_url);
        setVal('_tpl_footer', tpl.default_footer);

        if (syncEditorAndSubject) {
            var subjEl = document.getElementById(prefix + '_email_subject');
            var lastSubj = window.EmailComposerState[prefix].lastPresetSubject || '';
            if (subjEl && (!subjEl.value.trim() || subjEl.value.trim() === lastSubj)) {
                subjEl.value = tpl.default_subject || '';
                window.EmailComposerState[prefix].lastPresetSubject = tpl.default_subject || '';
            }
        }

        document.querySelectorAll('.tpl-preset-card[data-prefix="' + prefix + '"]').forEach(function (card) {
            if (card.getAttribute('data-template-id') === tpl.id) {
                card.classList.add('border-primary', 'shadow-sm');
                card.classList.remove('border-gray-200');
            } else {
                card.classList.remove('border-primary', 'shadow-sm');
                card.classList.add('border-gray-200');
            }
        });

        renderTemplatePreviewAndSync(prefix, syncEditorAndSubject);
    }

    function isTemplateTabActive(prefix) {
        var pane = document.getElementById(prefix + '_tab_template');
        return !!(pane && pane.classList.contains('active'));
    }

    function syncComposerBeforeSubmit(prefix) {
        if (isTemplateTabActive(prefix)) {
            renderTemplatePreviewAndSync(prefix, true);
        } else {
            var textareaId = prefix + '_email_body';
            if (window.tinymce && tinymce.get(textareaId)) {
                tinymce.get(textareaId).save();
            }
        }
    }

    function setupWrapper(wrapper) {
        if (wrapper.dataset.initialized === '1') {
            initEmailTinyMCE(wrapper.getAttribute('data-prefix'));
            return;
        }
        wrapper.dataset.initialized = '1';

        var prefix = wrapper.getAttribute('data-prefix');
        var formId = wrapper.getAttribute('data-form-id');

        initEmailTinyMCE(prefix);
        loadTemplateIntoFields(prefix, TEMPLATES[0] ? TEMPLATES[0].id : '', false);

        var formEl = document.getElementById(formId);
        if (formEl && !formEl.dataset.emailSyncBound) {
            formEl.dataset.emailSyncBound = '1';
            formEl.addEventListener('submit', function () {
                syncComposerBeforeSubmit(prefix);
            }, true);

            formEl.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    syncComposerBeforeSubmit(prefix);
                });
            });
        }
    }

    function initAllComposers() {
        document.querySelectorAll('.email-composer-wrapper').forEach(setupWrapper);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllComposers);
    } else {
        initAllComposers();
    }
    window.addEventListener('load', initAllComposers);

    if (!window.__emailComposerEventsBound) {
        window.__emailComposerEventsBound = true;

        document.addEventListener('click', function (e) {
            var card = e.target.closest('.tpl-preset-card');
            if (card) {
                loadTemplateIntoFields(
                    card.getAttribute('data-prefix'),
                    card.getAttribute('data-template-id'),
                    true
                );
                return;
            }

            var clearBtn = e.target.closest('.tpl-clear-image');
            if (clearBtn) {
                var cPrefix = clearBtn.getAttribute('data-prefix');
                var imgInput = document.getElementById(cPrefix + '_tpl_image_url');
                if (imgInput) imgInput.value = '';
                renderTemplatePreviewAndSync(cPrefix, true);
                return;
            }

            var applyBtn = e.target.closest('.tpl-apply-to-editor');
            if (applyBtn) {
                var aPrefix = applyBtn.getAttribute('data-prefix');
                renderTemplatePreviewAndSync(aPrefix, true);
                var customTabBtn = document.getElementById(aPrefix + '_tab_custom_btn');
                if (customTabBtn) customTabBtn.click();
                return;
            }

            var resetBtn = e.target.closest('.tpl-reset-custom-btn');
            if (resetBtn) {
                var rPrefix = resetBtn.getAttribute('data-prefix');
                var wrapper = document.querySelector('.email-composer-wrapper[data-prefix="' + rPrefix + '"]');
                var isBulk = wrapper && wrapper.getAttribute('data-is-bulk') === '1';
                setEditorContent(
                    rPrefix,
                    isBulk ? '<p>Hi [name],</p><p>Write your message here...</p>' : '<p>Hi,</p><p>Write your message here...</p>'
                );
                var backBtn = document.getElementById(rPrefix + '_tab_custom_btn');
                if (backBtn) backBtn.click();
                return;
            }

            var varBtn = e.target.closest('.insert-email-var');
            if (varBtn) {
                var targetId = varBtn.getAttribute('data-target');
                var token = varBtn.getAttribute('data-var');
                if (window.tinymce && tinymce.get(targetId)) {
                    tinymce.get(targetId).execCommand('mceInsertContent', false, token);
                    tinymce.get(targetId).save();
                } else {
                    var el = document.getElementById(targetId);
                    if (el) el.value += ' ' + token;
                }
                return;
            }

            var tplTabBtn = e.target.closest('[id$="_tab_template_btn"]');
            if (tplTabBtn) {
                var tId = tplTabBtn.getAttribute('id') || '';
                var tPrefix = tId.replace('_tab_template_btn', '');
                setTimeout(function () {
                    renderTemplatePreviewAndSync(tPrefix, true);
                }, 20);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.tpl-preset-card');
            if (card) {
                e.preventDefault();
                loadTemplateIntoFields(
                    card.getAttribute('data-prefix'),
                    card.getAttribute('data-template-id'),
                    true
                );
            }
        });

        document.addEventListener('input', function (e) {
            var field = e.target.closest('.tpl-field');
            if (field) {
                renderTemplatePreviewAndSync(field.getAttribute('data-prefix'), true);
            }
        });

        document.addEventListener('change', function (e) {
            var field = e.target.closest('.tpl-field');
            if (field) {
                renderTemplatePreviewAndSync(field.getAttribute('data-prefix'), true);
                return;
            }

            var uploadInput = e.target.closest('.email-inline-upload');
            if (uploadInput) {
                var file = uploadInput.files && uploadInput.files[0];
                if (!file) return;

                var prefix = uploadInput.getAttribute('data-target');
                var mode = uploadInput.getAttribute('data-mode');
                var statusEl = document.getElementById(prefix + '_inline_upload_status');

                if (statusEl) {
                    statusEl.classList.remove('d-none', 'text-danger', 'text-success');
                    statusEl.classList.add('text-primary');
                    statusEl.textContent = 'Uploading image...';
                }

                uploadEmailImageFile(
                    file,
                    function (url) {
                        uploadInput.value = '';
                        if (mode === 'template') {
                            var urlInput = document.getElementById(prefix + '_tpl_image_url');
                            if (urlInput) urlInput.value = url;
                            renderTemplatePreviewAndSync(prefix, true);
                        } else {
                            var targetId = prefix + '_email_body';
                            var imgHtml = '<p style="text-align:center;"><img src="' + escapeHtml(url) + '" alt="Email Image" style="max-width:100%;height:auto;border-radius:8px;" /></p>';
                            if (window.tinymce && tinymce.get(targetId)) {
                                tinymce.get(targetId).execCommand('mceInsertContent', false, imgHtml);
                                tinymce.get(targetId).save();
                            } else {
                                var bodyEl = document.getElementById(targetId);
                                if (bodyEl) bodyEl.value = imgHtml + '\n' + bodyEl.value;
                            }
                            if (statusEl) {
                                statusEl.classList.remove('text-primary');
                                statusEl.classList.add('text-success');
                                statusEl.textContent = 'Image uploaded and inserted into email body.';
                            }
                        }
                    },
                    function (msg) {
                        uploadInput.value = '';
                        if (statusEl) {
                            statusEl.classList.remove('d-none', 'text-primary');
                            statusEl.classList.add('text-danger');
                            statusEl.textContent = msg;
                        } else {
                            alert(msg);
                        }
                    }
                );
            }
        });
    }
})();
</script>
@endonce

