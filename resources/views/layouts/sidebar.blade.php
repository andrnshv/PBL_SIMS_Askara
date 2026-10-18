
@php
    use App\Helpers\MenuHelper;

    $menuGroups = MenuHelper::getMenuGroups();
@endphp

<aside
    id="sidebar"
    class="fixed flex flex-col mt-0 top-0 start-0 bg-[#0B1F3A] border-[#16305A] text-white h-screen transition-all duration-300 ease-in-out z-99999 ltr:border-r rtl:border-l border-gray-200 w-[90px] [.sidebar-expanded_&]:min-w-[290px]"
    x-data="{
        openSubmenus: {},

        toggleSubmenu(key) {
            const wasOpen = this.openSubmenus[key] === true;
            this.openSubmenus = {};
            this.openSubmenus[key] = !wasOpen;
        },

        isSubmenuOpen(key) {
            return this.openSubmenus[key] === true;
        }
    }"
    x-init="
        @foreach ($menuGroups as $group)
            @foreach ($group['items'] as $item)
                @if (!empty($item['subItems']))
                    if (
                        {{ collect($item['subItems'])->contains(fn ($sub) => MenuHelper::isActive($sub['path'])) ? 'true' : 'false' }}
                    ) {
                        openSubmenus[@js($item['name'])] = true;
                    }
                @endif
            @endforeach
        @endforeach
    "
    :class="{
        'translate-x-0': $store.sidebar.isMobileOpen,
        'max-xl:-translate-x-full max-xl:rtl:translate-x-full': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)"
>
    {{-- Logo --}}
    <div class="pt-8 pb-7 px-3">
        <img
            src="{{ asset('images/assets/logo-askara.png') }}"
            alt="Logo PT Askara"
            class="mx-auto h-auto w-full max-w-[200px] object-contain"
            style="filter:
                drop-shadow(2px 0 0 #fff)
                drop-shadow(-2px 0 0 #fff)
                drop-shadow(0 2px 0 #fff)
                drop-shadow(0 -2px 0 #fff);"
        >
    </div>

    {{-- Menu Groups --}}
    <nav class="sidebar-scroll mb-6 overflow-y-auto overflow-x-hidden flex-1">
        @foreach ($menuGroups as $group)
            <div class="mb-6">
                {{-- Group Title --}}
                <h3 class="px-5 mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 hidden [.sidebar-expanded_&]:block">
                    {{ $group['title'] }}
                </h3>

                <ul class="flex flex-col gap-1">
                    @foreach ($group['items'] as $item)
                        @php
                            $hasSubItems = !empty($item['subItems']);
                            $itemKey = $item['name'];

                            $isItemActive = isset($item['path'])
                                && MenuHelper::isActive($item['path']);

                            $isSubItemActive = $hasSubItems
                                && collect($item['subItems'])->contains(
                                    fn ($sub) => MenuHelper::isActive($sub['path'])
                                );

                            $isActive = $isItemActive || $isSubItemActive;
                        @endphp

                        <li>
                            @if ($hasSubItems)
                                {{-- Dropdown Trigger --}}
                                <button
                                    type="button"
                                    @click="toggleSubmenu(@js($itemKey))"
                                    :aria-expanded="isSubmenuOpen(@js($itemKey))"
                                    class="menu-item group w-full justify-center [.sidebar-expanded_&]:justify-start {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                >
                                    <span class="{{ $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                        {!! MenuHelper::getIconSvg($item['icon']) !!}
                                    </span>

                                    <span class="menu-item-text hidden [.sidebar-expanded_&]:inline flex-1 text-left">
                                        {{ $item['name'] }}
                                    </span>

                                    <svg
                                        class="w-4 h-4 hidden [.sidebar-expanded_&]:block transition-transform duration-200"
                                        :class="{ 'rotate-180': isSubmenuOpen(@js($itemKey)) }"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="m6 9 6 6 6-6"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </button>

                                {{-- Dropdown Items --}}
                                <div
                                    x-cloak
                                    x-show="isSubmenuOpen(@js($itemKey)) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)"
                                    x-collapse
                                    class="overflow-hidden"
                                >
                                    <ul class="mt-1 mb-2 flex flex-col gap-1">
                                        @foreach ($item['subItems'] as $subItem)
                                            @php
                                                $isSubActive = MenuHelper::isActive($subItem['path']);
                                            @endphp

                                            <li>
                                                <a
                                                    href="{{ url($subItem['path']) }}"
                                                    @if ($isSubActive) aria-current="page" @endif
                                                    class="menu-dropdown-item ml-4 pl-9 pr-3 py-2 flex items-center text-sm rounded-lg transition-colors {{ $isSubActive ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                >
                                                    <span>{{ $subItem['name'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                {{-- Regular Menu Item --}}
                                <a
                                    href="{{ url($item['path']) }}"
                                    @if ($isActive) aria-current="page" @endif
                                    class="menu-item group justify-center [.sidebar-expanded_&]:justify-start {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                >
                                    <span class="{{ $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                        {!! MenuHelper::getIconSvg($item['icon']) !!}
                                    </span>

                                    <span class="menu-item-text hidden [.sidebar-expanded_&]:inline">
                                        {{ $item['name'] }}
                                    </span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Tagline --}}
    <p class="mt-auto mb-10 px-5 hidden [.sidebar-expanded_&]:block text-theme-sm leading-snug text-gray-300">
        Manage Your Inventory,<br>
        Boost Your Sales,<br>
        Grow Your Business.
    </p>
</aside>
