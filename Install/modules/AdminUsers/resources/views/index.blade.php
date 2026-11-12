@extends('layouts.app')

@section('sub_header')
    <x-sub-header 
        title="{{ __('Users') }}" 
        description="{{ __('Easily Manage and Monitor All Platform Users') }}" 
        :count="$total_user"
    >
        <a class="btn btn-primary btn-sm" href="{{ url_admin("users/export") }}">
            <span><i class="fa-light fa-file-export"></i></span>
            <span>{{ __('Export') }}</span>
        </a>
        <a class="btn btn-dark btn-sm" href="{{ url_admin("users/create") }}">
            <span><i class="fa-light fa-user-plus"></i></span>
            <span>{{ __('Add user') }}</span>
        </a>
    </x-sub-header>
@endsection



@section('content')
<div class="container">

    <div class="row">
        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-primary d-flex align-items-center justify-content-center bg-primary-100 b-r-10">
                            <i class="fa-light fa-user-check"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __("Active") }}</div>
                            <div class="fw-7 fs-16">{{ safe_format_number($total_active_user) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-warning d-flex align-items-center justify-content-center bg-warning-100 b-r-10">
                            <i class="fa-light fa-user-check"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __("Inactive") }}</div>
                            <div class="fw-7 fs-16">{{ safe_format_number($total_inactive_user) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-danger d-flex align-items-center justify-content-center bg-danger-100 b-r-10">
                            <i class="fa-light fa-user-check"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __("Banned") }}</div>
                            <div class="fw-7 fs-16">{{ safe_format_number($total_banned_user) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
@component('components.datatable', [ "Datatable" => $Datatable ]) @endcomponent
@endsection

@section('script') 
    @component('components.datatable_script', [ "Datatable" => $Datatable, "edit_popup" => "" , "edit_url" => "" , "column_actions" => false, "column_status" => true]) @endcomponent
    <script type="text/javascript">
        columnDefs = columnDefs.concat([
            {
                targets: 'phone:name',
                orderable: true,
                className: 'min-w-120 text-start',
                render: function (data, type, row) {
                    if (row.phone) {
                        var code = row.country_code ? row.country_code + ' ' : '';
                        return `<div class="d-inline-flex align-items-center gap-6 px-2 py-1 b-r-6 bg-success-subtle text-success fs-12 fw-5 border border-success-subtle"><i class="fa-brands fa-whatsapp text-success fs-14"></i> <span>${code}${row.phone}</span></div>`;
                    }
                    return `<span class="text-gray-400 fs-12">-</span>`;
                }
            },

            {
                targets: 'fullname:name',
                orderable: true,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex gap-8 align-items-center">
                            <a class="size-40 size-child border b-r-6 text-gray-800" href="{{ module_url("edit") }}/${row.id_secure}">
                                <img data-src="${ Main.mediaURL('{{ Media::url() }}', row.avatar) }" src="${ Main.text2img(row.fullname, '000') }" class="b-r-6 lazyload" onerror="this.src='${ Main.text2img(row.fullname, '000') }'">
                            </a>
                            <div class="text-start lh-1 text-truncate">
                                <div class="fw-5 text-gray-900 text-truncate">
                                    <div class="text-truncate">
                                        <a class="text-gray-800 text-hover-primary" href="{{ module_url("edit") }}/${row.id_secure}">
                                            ${row.fullname}
                                        </a>
                                    </div>
                                    <div class="text-truncate text-gray-500 fs-12">
                                        ${row.email}
                                    </div>
                                    ${row.phone ? `<div class="text-truncate fs-11 text-success fw-5 mt-1"><i class="fa-brands fa-whatsapp me-1"></i>${row.country_code ? row.country_code + ' ' : ''}${row.phone}</div>` : ''}
                                </div>
                            </div>
                        </div>`;
                }
            },
            {
                targets: ['plan_name:name', 'plans.name:name', 4],
                orderable: true,
                className: 'min-w-100 text-start',
                render: function (data, type, row) {
                    var p = (row.plan_name || (typeof data === 'string' && isNaN(Number(data)) ? data : '') || '').toString().trim();
                    if (p !== '' && p !== '0') {
                        return `<span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12 fw-7"><i class="fa-solid fa-crown me-1 text-warning"></i> ${p}</span>`;
                    }
                    return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-11 fw-6"><i class="fa-solid fa-triangle-exclamation me-1"></i> {{ __("No Plan") }}</span>`;
                }
            },
            {
                targets: ['payment_details:name', 'users.payment_method:name', 5],
                orderable: false,
                className: 'min-w-160 text-start',
                render: function (data, type, row) {
                    var method = (row.payment_method || (typeof data === 'string' && isNaN(Number(data)) ? data : '') || 'free').toString().toLowerCase();
                    if (!isNaN(Number(method)) && method !== '') {
                        method = 'free';
                    }
                    var status = (row.payment_status || 'approved').toString().toLowerCase();
                    var ref = row.payment_reference || '';
                    var proof = row.payment_proof || '';
                    var id_secure = row.id_secure || '';

                    var html = '<div class="d-flex flex-column gap-1">';

                    if (method === 'upi') {
                        html += '<div class="d-flex align-items-center gap-1 flex-wrap">' +
                                '<span class="badge bg-success-subtle text-success border border-success-subtle fs-11 fw-7"><i class="fa-solid fa-qrcode me-1"></i> UPI</span>';
                        if (status === 'approved' || status == '1') {
                            html += '<span class="badge bg-success text-white fs-10 px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> {{ __("Approved") }}</span>';
                        } else if (status === 'rejected' || status == '2') {
                            html += '<span class="badge bg-danger text-white fs-10 px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i> {{ __("Rejected") }}</span>';
                        } else {
                            html += '<span class="badge bg-warning text-dark fs-10 px-2 py-1"><i class="fa-solid fa-clock me-1"></i> {{ __("Pending") }}</span>';
                        }
                        html += '</div>';

                        if (ref) {
                            html += '<div class="fs-11 font-monospace text-dark fw-bold">UTR: <span class="text-primary">' + ref + '</span></div>';
                        }

                        if (proof) {
                            var safeProof = proof.replace(/"/g, '&quot;');
                            var safeTitle = (ref ? ('UTR: ' + ref) : 'Payment Proof').replace(/"/g, '&quot;');
                            html += '<div class="d-flex align-items-center gap-1 mt-1">' +
                                    '<a href="javascript:void(0)" onclick="viewProofModal(\'' + safeProof + '\', \'' + safeTitle + '\')" class="btn btn-xs btn-outline-success py-0 px-2 fs-10 rounded-pill fw-6">' +
                                    '<i class="fa-solid fa-receipt me-1"></i> {{ __("View Proof") }}</a></div>';
                        }

                        if (status !== 'approved' && status != '1') {
                            html += '<div class="d-flex align-items-center gap-1 mt-1 flex-wrap">' +
                                    '<button type="button" class="btn btn-xs btn-success py-1 px-2 fs-11 rounded shadow-sm fw-7" onclick="approveUserUpi(\'' + id_secure + '\')">' +
                                    '<i class="fa-solid fa-check me-1"></i> {{ __("Approve") }}</button>' +
                                    '<button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 fs-11 rounded shadow-sm fw-7" onclick="rejectUserUpi(\'' + id_secure + '\')">' +
                                    '<i class="fa-solid fa-xmark me-1"></i> {{ __("Reject") }}</button>' +
                                    '</div>';
                        }
                    } else if (method === 'razorpay') {
                        html += '<div class="d-flex align-items-center gap-1 flex-wrap">' +
                                '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-11 fw-7"><i class="fa-solid fa-credit-card me-1"></i> Razorpay</span> ' +
                                '<span class="badge bg-success text-white fs-10 px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> {{ __("Approved") }}</span></div>';
                        if (ref) {
                            html += '<div class="fs-11 font-monospace text-muted">ID: <span class="text-dark fw-6">' + ref + '</span></div>';
                        }
                    } else {
                        html += '<div class="d-flex align-items-center gap-1 flex-wrap">' +
                                '<span class="badge bg-light text-secondary border fs-11 fw-6"><i class="fa-solid fa-gift me-1"></i> Free Plan</span> ' +
                                '<span class="badge bg-success text-white fs-10 px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> {{ __("Active") }}</span></div>';
                    }

                    html += '</div>';
                    return html;
                }
            },
            {
                targets: 'role:name',
                orderable: true,
                className: 'min-w-80 text-danger text-center',
                render: function (data, type, row){
                
                    if (row.role == 2) {
                        return `<span class="text-warning fs-18" title="{{ __("Admin") }}"><i class="fa-duotone fa-solid fa-crown"></i></span>`;
                    }else{
                        return `<span class="text-gray-500 fs-18" title="{{ __("User") }}"><i class="fa-duotone fa-user"></i></span>`;
                    }
                    
                }
            },
            {
            targets: -1,
            data: null,
            orderable: false,
            className: 'text-end',
            render: function (data, type, row) {
                return `
                    <div class="dropdown">
                        <button class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __("Actions") }}
                        </button>
                        <ul class="dropdown-menu border-1 border-gray-300 w-auto max-w-180 min-w-150">
                            <li class="mx-2">
                                <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6" href="{{ module_url("edit") }}/${row.id_secure}">
                                    <span class="size-16 me-1 text-center"><i class="fa-light fa-pen-to-square"></i></span>
                                    <span>{{ $edit_text ?? __("Edit") }}</span>
                                </a>
                            </li>
                            <li class="mx-2">
                                <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6" href="{{ url("auth/view-as-user") }}/${row.id_secure}">
                                    <span class="size-16 me-1 text-center"><i class="fa-light fa-eye"></i></span>
                                    <span>{{ $edit_text ?? __("View As User") }}</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            @if(!isset($delete) || $delete)
                            <li class="mx-2">
                                <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="{{ module_url("destroy") }}" data-confirm="{{ __("Are you sure you want to delete this item?") }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                    <span class="size-16 me-1 text-center"><i class="fa-light fa-trash-can-list"></i></span>
                                    <span>{{ __("Delete") }}</span>
                                </a>
                            </li>
                            @endif        
                        </ul>
                    </div>
                `;
            },
        },          
        ]);
        var dtConfig = {
            columns: {!! json_encode($Datatable['columns'] ?? []) !!},
            lengthMenu: {!! json_encode($Datatable['lengthMenu'] ?? []) !!},
            order: {!! json_encode($Datatable['order'] ?? []) !!},
            columnDefs: {!! json_encode($Datatable['columnDefs'] ?? []) !!}
        };

        dtConfig.columnDefs = dtConfig.columnDefs.concat(columnDefs);
        var DataTable = Main.DataTable("#{{ $Datatable['element'] }}", dtConfig);
        DataTable.columns(['email:name', 'avatar:name']).visible(false);

        function viewProofModal(url, title) {
            var modalEl = document.getElementById('paymentProofModal');
            var imgEl = document.getElementById('proofModalImage');
            var pdfLink = document.getElementById('proofModalPdfLink');
            var titleEl = document.getElementById('proofModalTitle');

            if (titleEl) titleEl.textContent = title || 'Payment Proof';

            var isPdf = url.toLowerCase().indexOf('.pdf') !== -1;
            if (isPdf) {
                if (imgEl) imgEl.style.display = 'none';
                if (pdfLink) {
                    pdfLink.href = url;
                    pdfLink.style.display = 'inline-flex';
                }
            } else {
                if (pdfLink) pdfLink.style.display = 'none';
                if (imgEl) {
                    imgEl.src = url;
                    imgEl.style.display = 'block';
                }
            }

            if (window.bootstrap && bootstrap.Modal) {
                var myModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                myModal.show();
            } else {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        }

        function closeProofModal() {
            var modalEl = document.getElementById('paymentProofModal');
            if (window.bootstrap && bootstrap.Modal) {
                var myModal = bootstrap.Modal.getInstance(modalEl);
                if (myModal) myModal.hide();
            }
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        function approveUserUpi(idSecure) {
            if (!confirm('{{ __("Are you sure you want to approve this UPI payment? The user plan will be activated immediately.") }}')) {
                return;
            }

            var token = '{{ csrf_token() }}';
            fetch('{{ url("admin/users/approve-upi") }}/' + idSecure, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ _token: token })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 1) {
                    if (window.Main && Main.showNotify) {
                        Main.showNotify('', data.message, 1);
                    } else {
                        alert(data.message);
                    }
                    if (window.Main && Main.DataTable_Reload) {
                        Main.DataTable_Reload('#DataTable');
                    } else {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Approval failed.');
                }
            })
            .catch(err => {
                alert('Network error while approving.');
            });
        }

        function rejectUserUpi(idSecure) {
            if (!confirm('{{ __("Are you sure you want to reject this UPI payment?") }}')) {
                return;
            }

            var token = '{{ csrf_token() }}';
            fetch('{{ url("admin/users/reject-upi") }}/' + idSecure, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ _token: token })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 1) {
                    if (window.Main && Main.showNotify) {
                        Main.showNotify('', data.message, 1);
                    } else {
                        alert(data.message);
                    }
                    if (window.Main && Main.DataTable_Reload) {
                        Main.DataTable_Reload('#DataTable');
                    } else {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Reject failed.');
                }
            })
            .catch(err => {
                alert('Network error while rejecting.');
            });
        }
    </script>

    <!-- Payment Proof Preview Modal -->
    <div class="modal fade" id="paymentProofModal" tabindex="-1" aria-labelledby="proofModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-receipt text-success fs-18"></i>
                        <h5 class="modal-title fs-15 fw-bold text-dark mb-0" id="proofModalTitle">{{ __('Payment Proof Receipt') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeProofModal()"></button>
                </div>
                <div class="modal-body text-center p-4 bg-light-subtle">
                    <img id="proofModalImage" src="" alt="Payment Proof" class="img-fluid rounded-3 border shadow-sm mx-auto" style="max-height: 520px; object-fit: contain; display: none;">
                    <a id="proofModalPdfLink" href="" target="_blank" class="btn btn-primary rounded-pill px-4 py-2 font-weight-bold" style="display: none;">
                        <i class="fa-solid fa-file-pdf me-2 fs-18"></i> {{ __('Open PDF Proof in New Tab') }}
                    </a>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal" onclick="closeProofModal()">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
