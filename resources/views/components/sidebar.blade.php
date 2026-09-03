<aside class="sidebar">

    {{-- Logo --}}
    <div class="logo-section">

        <svg
            class="logo-icon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"
            />
        </svg>

        <div class="name-section">
            <h2>MOSHA AI</h2>
        </div>

    </div>


    {{-- Sidebar Buttons --}}
    <div class="btns-sidebar">

        {{-- New Chat --}}
        <form action="{{ route('chats.store') }}" method="POST">
            @csrf

            <button type="submit" class="btn">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                New Chat

            </button>
        </form>


        {{-- Library --}}
        <button type="button" class="btn">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6a2 2 0 012-2h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"
                />
            </svg>

            Library

        </button>


        {{-- Projects --}}
        <button type="button" class="btn">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                />
            </svg>

            Projects

        </button>


        {{-- Plugins --}}
        <button type="button" class="btn">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"
                />
            </svg>

            Plugins

        </button>

    </div>


    {{-- Recents title --}}
    <div class="tiettle-recents">
        <h3>Recents</h3>
    </div>


    {{-- Recents --}}
    <div class="recents">

        <div class="recent-list">

            <ul>

                @forelse($chats as $recentChat)

                    <li>
                        <a
                            href="{{ route('chats.show', $recentChat) }}"
                            class="{{ isset($chat) && $chat->id === $recentChat->id ? 'active-chat' : '' }}"
                        >
                            {{ $recentChat->title }}
                        </a>
                    </li>

                @empty

                    <li>
                        <span>No chats yet.</span>
                    </li>

                @endforelse

            </ul>

        </div>

    </div>


    {{-- Account --}}
    <div class="account">

        <div class="account-container">

            <div class="img-account">

                <img
                    src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name . ' ' . Auth::user()->last_name) }}&background=0D8ABC&color=fff"
                    alt="Profile Picture"
                >

            </div>


            <div class="name-status">

                <div class="name">

                    <h4>
                        {{ Auth::user()->name }}
                        {{ Auth::user()->last_name }}
                    </h4>

                </div>

                <div class="status">
                    <h5>Free</h5>
                </div>

            </div>

        </div>


        {{-- Logout --}}
        <form action="{{ route('logout') }}" method="POST">

            @csrf

            <button type="submit" class="logout-btn">
                Logout
            </button>

        </form>

    </div>

</aside>
