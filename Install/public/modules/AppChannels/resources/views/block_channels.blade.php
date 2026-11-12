@php
$permission = $permission ?? 'appchannels';
$teamId = request()->team_id;
$socialNetwork = $social_network ?? null;
$socialNetworks = $social_networks ?? null;
$moduleName = $module_name ?? null;
$category = $category ?? null;
$loginType = $login_type ?? null;
$canPost = $can_post ?? null;
$emptyText = $empty_text ?? __('Please select a channel');
$emptyResultText = $empty_result_text ?? __('No channels found for the current filters.');

$query = DB::table('accounts')->where('status', 1);

if ($teamId) {
    $query->where('team_id', $teamId);
}

if (is_string($socialNetwork) && $socialNetwork !== '') {
    $query->where('social_network', $socialNetwork);
} elseif (is_array($socialNetworks) && !empty($socialNetworks)) {
    $query->whereIn('social_network', array_values(array_filter($socialNetworks)));
}

if (is_string($moduleName) && $moduleName !== '') {
    $query->where('module', $moduleName);
}

if (is_string($category) && $category !== '') {
    $query->where('category', $category);
}

if ($loginType !== null && $loginType !== '') {
    $query->where('login_type', $loginType);
}

$canPost = ($canPost === '' || $canPost === null) ? null : (int) $canPost;

if ($canPost !== null) {
    $query->where('can_post', $canPost);
}

$channels = $query
    ->orderBy('social_network')
    ->orderBy('name')
    ->get();

$groups = DB::table('groups')
    ->where('team_id', $teamId)
    ->orderBy('name')
    ->get();
@endphp

<div class="account_manager w-100">
    <div class="am-choice-box">
        <div class="am-selected-box rounded border pf-10 d-flex align-items-center justify-content-between">
            <div class="overflow-y-auto flex-grow-1 max-h-90 me-3">
                <button type="button" class="am-open-list-account"></button>
                <div class="am-selected-empty">
                    <div class="d-flex gap-8  align-items-center">
                        <i class="fa-light fa-chart-network"></i>
                        <span class="fw-5 text-gray-700 fs-14">{{ $emptyText }}</span>
                    </div>
                </div>
                <div class="am-selected-list">
                </div>
            </div>
            <div class="am-selected-arrow">
                <i class="fal fa-chevron-up"></i>
            </div>
        </div>

        <div class="am-list-account border rounded bg-white check-wrap-all">
            <div class="p-3">
                <div class="input-group">
                    <div class="form-control">
                        <i class="fa-light fa-magnifying-glass"></i>
                        <input class="search-input" data-search="search-accounts" placeholder="{{ __('Search') }}" type="text" value="">
                    </div>
                    <span class="btn btn-icon btn-input">
                        <div class="form-check">
                            <input class="form-check-input checkbox-all" type="checkbox" value="" data-checkbox-parent=".am-list-account">
                        </div>
                    </span>
                    @if(Access::permission('appgroups'))
                    <div class="dropdown-toggle dropdown-arrow-hide btn btn-icon btn-input border" data-bs-toggle="dropdown" aria-expanded="true">
                        <i class="fa-light fa-user-group"></i>
                    </div>
                    <div class="btn-group position-static">
                        <ul class="dropdown-menu dropdown-menu-end border-1 border-gray-300 px-2 w-100 max-w-180">
                            @if ($groups->count())
                                @foreach ($groups as $value)
                                    @php
                                    $accountsArr = is_array($value->accounts) ? $value->accounts : json_decode($value->accounts, true);
                                    @endphp

                                    <li>
                                        <a class="dropdown-item p-2 rounded d-flex gap-8 fw-5 fs-14 text-truncate select-group"
                                            href="javascript:void(0);"
                                            data-accounts='@json($accountsArr)'>
                                            <span class="size-16 me-1 text-center"><i class="fa-light fa-user-check"></i></span>
                                            <span class="text-truncate">{{ $value->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li>
                                    <a href="{{ url_app('groups') }}" class="btn btn-dark btn-sm wp-100">{{ __('Add new') }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    @endif
                </div>
            </div>

            <div class="am-choice-body max-h-400 overflow-auto">
                @if ($channels->count())
                    @foreach ($channels as $value)
                        @can($permission . "." . strtolower($value->module))
                            <div class="search-accounts">
                                <label class="am-choice-item d-flex gap-12 border-top px-3 py-3"
                                    for="am_{{ $value->id }}"
                                    data-pid="{{ $value->pid }}"
                                    data-social-network="{{ $value->social_network }}"
                                    data-avatar="{{ Media::url($value->avatar) }}"
                                    data-username="{{ $value->username }}"
                                    data-name="{{ $value->name }}">
                                    <div class="size-40">
                                        <img src="{{ Media::url($value->avatar) }}" class="wp-100 hp-100 b-r-6 border">
                                    </div>
                                    <div class="d-flex align-items-center flex-grow-1 text-truncate">
                                        <div class="flex-grow-1 me-2 text-truncate">
                                            <div class="text-gray-800 text-hover-primary fs-12 fw-bold text-truncate">
                                                {{ $value->name }}
                                                {!! $value->login_type != 1 ? '<span class="text-danger-400">'.__('(Unofficial)').'</span>' : '' !!}
                                            </div>
                                            <span class="text-gray-600 fw-semibold d-block fs-10 text-truncate">
                                                {{ __(ucfirst($value->social_network . ' ' . $value->category)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input checkbox-item" type="checkbox"
                                            id="am_{{ $value->id }}" name="accounts[]"
                                            value="{{ $value->id_secure }}" @checked(isset($accounts) && in_array($value->id, $accounts))>
                                    </div>
                                    <div class="am-choice-item-selected d-none">
                                        <div class="am-selected-item border rounded p-2 me-2 min-w-100 max-w-150 float-start mb-1"
                                            data-id="{{ $value->id_secure }}" data-network="{{ $value->social_network }}">
                                            <div class="d-flex align-items-center gap-8">
                                                <div class="size-20 min-w-20">
                                                    <img src="{{ Media::url($value->avatar) }}"
                                                         class="d-flex wp-100 hp-100 b-r-6 border {{ $value->login_type!=1 ? 'border-danger-300' : '' }}">
                                                </div>
                                                <div class="d-flex align-items-center text-truncate">
                                                    <div class="text-gray-800 fs-12 fw-bold text-truncate">{{ $value->name }}</div>
                                                </div>
                                                <a href="javascript:void(0);" class="d-flex align-items-center m-r-10 remove">
                                                    <div class="text-gray-800 text-hover-danger fs-12 fw-bold ps-2"><i class="fal fa-times"></i></div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endcan
                    @endforeach
                @else
                    <div class="px-3 py-4 border-top text-gray-500 fs-13">{{ $emptyResultText }}</div>
                @endif
            </div>

            <div class="am-choice-footer border-top pf-15">
                <a href="{{ route('app.channels.add') }}" class="btn btn-dark w-100">
                    <i class="fal fa-plus"></i> {{ __('Connect a channel') }}
                </a>
            </div>
        </div>
    </div>
</div>
