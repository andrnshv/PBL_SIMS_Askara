
<div
    class="relative"
    x-data="{
        isOpen: false,

        toggleDropdown() {
            this.isOpen = !this.isOpen;
        },

        closeDropdown() {
            this.isOpen = false;
        }
    }"
    @click.outside="closeDropdown()"
>
    {{-- User Trigger --}}
    <button
        class="flex items-center text-gray-700 dark:text-gray-400"
        type="button"
        @click="toggleDropdown()"
    >
        {{-- User Avatar Initial --}}
        <span
            class="mr-3 flex h-11 w-11 items-center justify-center rounded-full bg-[#0B666A] text-lg font-semibold text-white rtl:mr-0 rtl:ml-3"
        >
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </span>

        {{-- User Name --}}
        <span class="block mr-1 font-medium text-theme-sm rtl:mr-0 rtl:ml-1">
            {{ auth()->user()->name ?? 'User' }}
        </span>

        {{-- Chevron Down Icon --}}
        <svg
            :class="isOpen ? 'rotate-180' : ''"
            class="transition-transform duration-200 stroke-gray-500 group-hover:stroke-gray-700 dark:stroke-gray-400 dark:group-hover:stroke-gray-200"
            width="18"
            height="18"
            viewBox="0 0 18 18"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M4.5 6.75L9 11.25L13.5 6.75"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute ltr:right-0 rtl:left-0 z-50 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
        style="display: none;"
    >
        {{-- User Info --}}
        <div>
            <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
                {{ auth()->user()->name ?? 'User' }}
            </span>

            <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
                {{ auth()->user()->email ?? '' }}
            </span>
        </div>

        {{-- Menu Items --}}
        <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
            {{-- Edit Profile --}}
            <li>
                <a
                    href="{{ route('profile') }}"
                    class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                        <svg
                            class="w-6 h-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM5 20a7 7 0 0 1 14 0"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>

                    Edit Profile
                </a>
            </li>
        </ul>

        {{-- Sign Out --}}
        <form
            action="{{ route('logout') }}"
            method="POST"
            class="mt-3"
        >
            @csrf

            <button
                type="submit"
                class="flex items-center w-full gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                @click="closeDropdown()"
            >
                <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"
                        />
                    </svg>
                </span>

                Sign Out
            </button>
        </form>
    </div>
</div>
