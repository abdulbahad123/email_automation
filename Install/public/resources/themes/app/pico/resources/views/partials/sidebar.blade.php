<div class="sidebar hide-scroll">

    <div class="sidebar-header d-flex align-items-center px-3 position-relative">
        <div class="d-flex align-items-center overflow-hidden" style="max-height: 56px;">
            <a href="{{ session('login_as') == "admin" ? route("admin.dashboard") : route("app.dashboard") }}" class="d-flex align-items-center text-decoration-none">
                <div class="logo-small">
                    <img src="{{ asset('img/whatsappbulklogo.png') }}" class="max-w-180" style="max-height: 46px; width: auto; object-fit: contain;" alt="Whatsappbulk" onerror="this.src='/img/whatsappbulklogo.png'">
                </div>
                <div class="logo-large">
                    <img src="{{ asset('img/whatsappbulklogo.png') }}" class="max-w-180" style="max-height: 52px; width: auto; object-fit: contain;" alt="Whatsappbulk" onerror="this.src='/img/whatsappbulklogo.png'">
                </div>
            </a>
        </div>

        {{-- Sidebar toggle removed on laptop/desktop so sidebar remains permanently fixed --}}
        <div class="sidebar-toggle bg-light" style="display: none !important;">
            <a href="javascript:void(0)" class="d-flex align-items-center justify-content-center border-1 px-2 rounded size-24 fs-14 d-block text-gray-500"><i class="fa-duotone fa-arrow-right-to-line"></i></a>
        </div>
    </div>

    @php
        $waUser = auth()->user() ?? (\Modules\Auth\Models\Auth::user() ?? null);
        $waIsLocked = false;
        try {
            if ($waUser && !empty($waUser->expiration_date) && $waUser->expiration_date != -1) {
                $expTimestamp = is_numeric($waUser->expiration_date) ? (int)$waUser->expiration_date : strtotime($waUser->expiration_date);
                if ($expTimestamp > 0 && (time() - $expTimestamp) >= 120) {
                    $waIsLocked = true;
                }
            }
        } catch (\Throwable $e) {}

        $isPayUri = function($uri) {
            $u = strtolower((string)$uri);
            return strpos($u, 'plan') !== false
                || strpos($u, 'pricing') !== false
                || strpos($u, 'subscription') !== false
                || strpos($u, 'payment') !== false
                || strpos($u, 'profile/plan') !== false
                || strpos($u, 'logout') !== false;
        };
    @endphp

    <style>
    @media (min-width: 768px) {
        .sidebar-toggle {
            display: none !important;
            pointer-events: none !important;
            visibility: hidden !important;
        }
        html.sidebar-small .sidebar,
        .sidebar,
        .sidebar:not(:hover),
        html.sidebar-small .sidebar:not(:hover) {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            height: 100vh !important;
            z-index: 1000 !important;
            transform: none !important;
            transition: none !important;
            display: flex !important;
            flex-direction: column !important;
            background-color: #0f172a !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.12) !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .sidebar .sidebar-header,
        html.sidebar-small .sidebar .sidebar-header {
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            height: 70px !important;
            min-height: 70px !important;
            max-height: 70px !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 16px !important;
            box-sizing: border-box !important;
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .sidebar .menu,
        html.sidebar-small .sidebar .menu,
        .sidebar:not(:hover) .menu,
        html.sidebar-small .sidebar:not(:hover) .menu {
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            flex-grow: 1 !important;
            height: calc(100vh - 70px) !important;
            max-height: calc(100vh - 70px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            padding: 10px 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-top,
        .sidebar .menu-bottom,
        html.sidebar-small .sidebar .menu-top,
        html.sidebar-small .sidebar .menu-bottom {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            padding: 0 10px !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .sidebar .menu-item,
        html.sidebar-small .sidebar .menu-item {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 1px 0 !important;
            text-align: left !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-link,
        html.sidebar-small .sidebar .menu-link,
        .sidebar:not(:hover) .menu-link,
        html.sidebar-small .sidebar:not(:hover) .menu-link {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding: 9px 12px !important;
            width: 100% !important;
            gap: 12px !important;
            border-radius: 8px !important;
            box-sizing: border-box !important;
            text-align: left !important;
        }

        .sidebar .menu-icon,
        html.sidebar-small .sidebar .menu-icon {
            width: 22px !important;
            min-width: 22px !important;
            max-width: 22px !important;
            margin: 0 !important;
            padding: 0 !important;
            text-align: center !important;
            flex-shrink: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 16px !important;
        }

        html.sidebar-small .sidebar .menu-item .menu-link .menu-title,
        .sidebar .menu-item .menu-link .menu-title,
        html.sidebar-small .sidebar:not(:hover) .menu-item .menu-link .menu-title,
        .sidebar:not(:hover) .menu-item .menu-link .menu-title {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            width: auto !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            flex-grow: 1 !important;
            font-size: 13.5px !important;
            line-height: 1.3 !important;
            text-align: left !important;
        }

        html.sidebar-small .sidebar .menu-item .menu-link .menu-arrow,
        .sidebar .menu-item .menu-link .menu-arrow,
        html.sidebar-small .sidebar:not(:hover) .menu-item .menu-link .menu-arrow,
        .sidebar:not(:hover) .menu-item .menu-link .menu-arrow {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-left: auto !important;
        }

        html.sidebar-small .sidebar .menu-heading,
        .sidebar .menu-heading,
        html.sidebar-small .sidebar:not(:hover) .menu-heading,
        .sidebar:not(:hover) .menu-heading {
            text-align: left !important;
            padding: 12px 12px 4px 12px !important;
        }

        html.sidebar-small .sidebar .menu-heading span,
        .sidebar .menu-heading span,
        html.sidebar-small .sidebar:not(:hover) .menu-heading span,
        .sidebar:not(:hover) .menu-heading span {
            display: inline-block !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
            opacity: 1 !important;
            visibility: visible !important;
            color: #64748b !important;
            text-transform: uppercase !important;
        }

        html.sidebar-small .sidebar:not(:hover) .menu-heading:before,
        .sidebar:not(:hover) .menu-heading:before {
            content: "" !important;
            display: none !important;
        }

        html.sidebar-small .sidebar .large-show,
        .sidebar .large-show,
        html.sidebar-small .sidebar:not(:hover) .large-show,
        .sidebar:not(:hover) .large-show,
        .sidebar .sidebar-header .logo-large {
            display: block !important;
        }

        html.sidebar-small .sidebar .small-show,
        .sidebar .small-show,
        html.sidebar-small .sidebar:not(:hover) .small-show,
        .sidebar:not(:hover) .small-show,
        .sidebar .sidebar-header .logo-small {
            display: none !important;
        }

        html.sidebar-small .sidebar .sidebar-blocks,
        .sidebar .sidebar-blocks,
        html.sidebar-small .sidebar:not(:hover) .sidebar-blocks,
        .sidebar:not(:hover) .sidebar-blocks {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .sidebar .menu-accordion {
            width: 100% !important;
            padding-left: 24px !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-accordion .menu-item {
            padding: 0 !important;
            margin: 1px 0 !important;
        }

        .sidebar .menu-accordion .menu-link {
            padding: 7px 10px !important;
            font-size: 13px !important;
        }
    }
    .wa-nav-locked {
        pointer-events: none !important;
        opacity: 0.42 !important;
        cursor: not-allowed !important;
        filter: grayscale(0.6);
    }
    .wa-lock-badge {
        background: #ef4444;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 5px;
        border-radius: 4px;
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .menu-link[data-bs-toggle="collapse"],
    .menu-link-accordion-toggle {
        cursor: pointer !important;
        user-select: none;
    }
    .sidebar .menu-arrow,
    .sidebar .menu-arrow i {
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    .sidebar .menu-arrow {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        padding: 3px 6px !important;
        border-radius: 4px;
        transition: background 0.15s ease;
    }
    .sidebar .menu-arrow:hover {
        background: rgba(255, 255, 255, 0.15) !important;
    }
    .menu-link.collapsed .menu-item-show {
        display: inline-block !important;
    }
    .menu-link.collapsed .menu-item-hide {
        display: none !important;
    }
    .menu-link:not(.collapsed) .menu-item-show {
        display: none !important;
    }
    .menu-link:not(.collapsed) .menu-item-hide {
        display: inline-block !important;
    }
    .menu-accordion.collapse:not(.show) {
        display: none !important;
    }
    .menu-accordion.collapse.show {
        display: block !important;
    }
    </style>

    <div class="menu d-flex flex-column align-items-stretch flex-row-auto" id="accordionMenu">

        @if($waIsLocked)
            <div class="px-3 py-2 my-2 mx-3 w-100" style="background: rgba(239, 68, 68, 0.12); border: 1.5px solid #ef4444; border-radius: 10px; text-align: center; max-width: calc(100% - 24px);">
                <div class="text-danger fw-bold fs-12 d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-lock me-1"></i> {{ __('Navigation Locked') }}
                </div>
                <div class="text-muted fs-11 mt-1 mb-2">
                    {{ __('Plan expired. Navigation locked. Please renew your plan.') }}
                </div>
                <a href="{{ url('/app/profile/plan') }}" class="btn btn-sm btn-danger wp-100 fw-bold py-1 fs-11 text-white text-decoration-none" style="border-radius: 6px; box-shadow: 0 2px 8px rgba(239,68,68,0.4);">
                    <i class="fa-solid fa-bolt me-1"></i> {{ __('Renew Plan Now') }}
                </a>
            </div>
        @endif
        
        @if( isset( $sidebar['top'] ) )
            <div class="menu-top d-flex flex-column flex-column-fluid w-100 mb-2">

                @php
                    $current_tab = 0;
                @endphp

                @foreach( $sidebar['top'] as $key => $value )

                    @if( $current_tab == 0 && isset( $value['tab_name'] ) )
                        <div class="menu-item pt-2">
                            <div class="menu-heading"><span>{{ __($value['tab_name']) }}</span></div>
                        </div>
                    @endif
                
                    @if($value['tab_id'] != $current_tab && $current_tab)
                        <div class="menu-item h-1 bg-gray-200 my-2"> </div>

                        @if( isset( $value['tab_name'] ) )
                        <div class="menu-item pt-2">
                            <div class="menu-heading"><span>{{ __($value['tab_name']) }}</span></div>
                        </div>
                        @endif
                    @endif

                    @php 
                        $current_tab = $value['tab_id'];
                    @endphp

                    @if(!isset($value['sub_menu']))
                        @php
                            $isItemLocked = $waIsLocked && !$isPayUri($value['uri'] ?? '');
                        @endphp
                        <div class="menu-item">
                            <a class="menu-link flex-grow-1 align-items-center {{ $isItemLocked ? 'wa-nav-locked' : '' }} {{ menu_active($value['uri']) ? 'text-primary' : '' }}" href="{{ $isItemLocked ? 'javascript:void(0)' : url( $value['uri'] ) }}">
                                <div class="menu-icon fs-18 {{ menu_active($value['uri']) ? 'text-primary' : 'text-gray-900' }}">
                                    <i class="{{ $value['icon'] }}" {!! Core::sidebarColor() !!}></i>
                                </div>
                                <div class="menu-title d-flex align-items-center flex-grow-1 fs-14 fw-5 text-truncate" {!! menu_active($value['uri']) ? Core::sidebarColor() : '' !!}>
                                    <div class="text-truncate">
                                        {{ __($value['name']) }}
                                    </div>
                                    @if($isItemLocked)
                                        <i class="fa-solid fa-lock text-danger ms-auto fs-12"></i>
                                    @endif
                                </div>
                            </a>
                        </div>

                        @if(($value['uri'] ?? '') == 'admin/plans')
                            <div class="menu-item">
                                <a class="menu-link flex-grow-1 align-items-center {{ request()->is('admin/expire-alert*') ? 'text-primary' : '' }}" href="{{ url('admin/expire-alert') }}">
                                    <div class="menu-icon fs-18 {{ request()->is('admin/expire-alert*') ? 'text-primary' : 'text-gray-900' }}">
                                        <i class="fa-light fa-bell-exclamation text-warning"></i>
                                    </div>
                                    <div class="menu-title d-flex align-items-center flex-grow-1 fs-14 fw-5 text-truncate">
                                        <div class="text-truncate">
                                            {{ __('Expire Alert') }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if(($value['uri'] ?? '') == 'admin/dashboard')
                            <div class="menu-item">
                                <a class="menu-link flex-grow-1 align-items-center {{ request()->is('admin/frontend/landing-page*') ? 'text-primary' : '' }}" href="{{ url('admin/frontend/landing-page') }}">
                                    <div class="menu-icon fs-18 {{ request()->is('admin/frontend/landing-page*') ? 'text-primary' : 'text-gray-900' }}">
                                        <i class="fa-light fa-rocket text-success"></i>
                                    </div>
                                    <div class="menu-title d-flex align-items-center flex-grow-1 fs-14 fw-5 text-truncate">
                                        <div class="text-truncate">
                                            {{ __('Landing Page & OTP') }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    @else
                        @php
                            $sub_menus = $value['sub_menu'];
                            $is_sub_active = false;
                            foreach($sub_menus as $sub_menu) {
                                if(menu_active($sub_menu['uri'])) {
                                    $is_sub_active = true;
                                    break;
                                }
                            }
                            $isAccordionLocked = $waIsLocked;
                            $menuCollapseId = 'menu-' . \Illuminate\Support\Str::slug($value['name'] ?? ('item-' . $key));
                        @endphp
                        <div class="menu-item">
                            <div role="button" tabindex="0" class="menu-link menu-link-accordion-toggle flex-grow-1 align-items-center {{ $is_sub_active ? '' : 'collapsed' }} {{ $isAccordionLocked ? 'wa-nav-locked' : '' }} {{ $is_sub_active ? 'text-primary' : '' }}" {{ $isAccordionLocked ? '' : 'data-menu-target="' . $menuCollapseId . '" aria-expanded="' . ($is_sub_active ? 'true' : 'false') . '" aria-controls="' . $menuCollapseId . '" onclick="toggleWhatsAppSidebarMenu(this, \'' . $menuCollapseId . '\', event)"' }}>
                                <div class="menu-icon fs-18 {{ $is_sub_active ? 'text-primary' : 'text-gray-900' }}">
                                    <i class="{{ $value["icon"] }}" {!! Core::sidebarColor() !!}></i>
                                </div>
                                <div class="menu-title d-flex align-items-center flex-grow-1 fs-14 fw-5" {!! $is_sub_active ? Core::sidebarColor() : '' !!}>
                                    {{ __($value["name"]) }}
                                    @if($isAccordionLocked)
                                        <i class="fa-solid fa-lock text-danger ms-auto fs-12"></i>
                                    @endif
                                </div>
                                @if(!$isAccordionLocked)
                                <div class="menu-arrow" role="button" tabindex="0" title="{{ __('Toggle Menu') }}" onclick="toggleWhatsAppSidebarMenu(this, '{{ $menuCollapseId }}', event)">
                                    <i class="fa-duotone fa-plus menu-item-show"></i>
                                    <i class="fa-duotone fa-minus menu-item-hide"></i>
                                </div>
                                @endif
                            </div>

                            @if(!$isAccordionLocked)
                            <div id="{{ $menuCollapseId }}" class="menu-accordion accordion-collapse collapse {{ $is_sub_active ? 'show' : '' }}" data-bs-parent="#accordionMenu">
                                @foreach($sub_menus as $sub_menu)
                                <div class="menu-item">
                                    <a class="menu-link {{ menu_active($sub_menu['uri']) ? 'active text-primary' : '' }}" href="{{ url($sub_menu['uri']) }}">
                                        <span class="menu-bullet"></span>
                                        <span class="menu-title d-flex align-items-center flex-grow-1 fs-13">{{ __($sub_menu['name']) }}</span>
                                    </a>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                    @endif

                @endforeach

            </div>
        @endif

        @if( isset( $sidebar['bottom'] ) )
            <div class="menu-bottom d-flex flex-column flex-column-fluid w-100 mt-auto">

                @foreach( $sidebar['bottom'] as $value )
                    @php
                        $isBottomLocked = $waIsLocked && !$isPayUri($value['uri'] ?? '');
                    @endphp
                    <div class="menu-item">
                        <a href="{{ $isBottomLocked ? 'javascript:void(0)' : url( $value['uri'] ) }}" class="menu-link flex-grow-1 align-items-center {{ $isBottomLocked ? 'wa-nav-locked' : '' }}">
                            <div class="menu-icon text-gray-600 fs-18">
                                <i class="{{ $value['icon'] }}" {!! Core::sidebarColor() !!}></i>
                            </div>
                            <div class="menu-title d-flex align-items-center flex-grow-1 fs-14 fw-5" {!! menu_active($value['uri']) ? Core::sidebarColor() : '' !!}>
                                {{ __($value['name']) }}
                                @if($isBottomLocked)
                                    <i class="fa-solid fa-lock text-danger ms-auto fs-12"></i>
                                @endif
                            </div>
                        </a>
                    </div>

                @endforeach
     
                @foreach(\Core::getSidebarBlocks() as $sidebarItem)
                    <div class="sidebar-blocks">
                        @php
                            $isVisible = $sidebarItem['visible'] ?? fn() => true;
                        @endphp
                        @if($isVisible())
                            {!! is_callable($sidebarItem['item']) ? $sidebarItem['item']() : $sidebarItem['item'] !!}
                        @endif
                    </div>
                @endforeach
            </div>

        @endif
    </div>

    <script>
        function toggleWhatsAppSidebarMenu(el, targetId, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            var triggerEl = (el && el.closest) ? (el.closest('.menu-link-accordion-toggle') || el) : el;
            if (!triggerEl || triggerEl.classList.contains('wa-nav-locked')) return;
            var targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            var isShown = targetEl.classList.contains('show');
            if (isShown) {
                targetEl.classList.remove('show');
                targetEl.style.display = 'none';
                triggerEl.classList.add('collapsed');
                triggerEl.setAttribute('aria-expanded', 'false');
            } else {
                var parentMenu = document.getElementById('accordionMenu');
                if (parentMenu) {
                    parentMenu.querySelectorAll('.menu-accordion').forEach(function(otherAcc) {
                        if (otherAcc.id !== targetId) {
                            otherAcc.classList.remove('show');
                            otherAcc.style.display = 'none';
                            var otherTrigger = parentMenu.querySelector('[data-menu-target="' + otherAcc.id + '"]')
                                || parentMenu.querySelector('[onclick*="' + otherAcc.id + '"]');
                            if (otherTrigger) {
                                var btn = otherTrigger.closest('.menu-link-accordion-toggle') || otherTrigger;
                                btn.classList.add('collapsed');
                                btn.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                }
                targetEl.classList.add('show');
                targetEl.style.display = 'block';
                triggerEl.classList.remove('collapsed');
                triggerEl.setAttribute('aria-expanded', 'true');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.menu-link-accordion-toggle').forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    var targetId = this.getAttribute('data-menu-target') || this.getAttribute('aria-controls');
                    if (targetId) {
                        toggleWhatsAppSidebarMenu(this, targetId, e);
                    }
                });
            });
            document.querySelectorAll('.sidebar .menu-arrow').forEach(function(arrow) {
                arrow.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var parent = this.closest('.menu-link-accordion-toggle');
                    if (parent) {
                        var targetId = parent.getAttribute('data-menu-target') || parent.getAttribute('aria-controls');
                        if (targetId) {
                            toggleWhatsAppSidebarMenu(parent, targetId, e);
                        }
                    }
                });
            });
        });

        @if($waIsLocked)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a.wa-nav-locked').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = "{{ url('profile/plan') }}";
                    return false;
                });
            });
        });
        @endif
    </script>

</div>