<div class="dropdown">

    <a href="#" class="nav-link pr-0 leading-none" data-toggle="dropdown">
        <span class="avatar" style="background-image: url({{ auth()->user()->avatar }})"></span>
        <span class="ml-2 d-none d-lg-block">
            <span class="text-default">{{ auth()->user()->name }}</span>
            {{-- <small class="text-muted d-block mt-1">Administrator</small> --}}
        </span>
    </a>

    <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
        <router-link to="/dashboard/settings" class="dropdown-item" active-class="none">
            <i class="dropdown-icon fe fe-settings"></i> Settings
        </router-link>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item" href="#" onclick="event.preventDefault();
            document.getElementById('logout-form').submit();">
            <i class="dropdown-icon fe fe-log-out"></i> Sign out
        </a>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

</div>
