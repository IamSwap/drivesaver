<div class="header py-4">
    <div class="container">
        <div class="d-flex">
            <router-link class="header-brand" to="/home">
                <h3 class="mb-0">{{ config('app.name') }}</h3>
            </router-link>

            @guest
            <div class="d-flex order-lg-2 ml-auto">
                <div class="nav-item d-none d-md-flex">
                    <a href="{{ url('auth') }}" class="btn btn-red">
                        <span class="fa fa-google"></span>
                        Login with Google
                    </a>
                </div>
            </div>
            @else
            <div class="d-flex order-lg-2 ml-auto">
                @include('partials.account-dropdown')
            </div>
            @endguest

            <a href="#" class="header-toggler d-lg-none ml-3 ml-lg-0" data-toggle="collapse" data-target="#headerMenuCollapse">
                <span class="header-toggler-icon"></span>
            </a>
        </div>
    </div>
</div>

<div class="header collapse d-lg-flex p-0" id="headerMenuCollapse">
    <div class="container">
        <div class="row align-items-center">
            @include('partials.navigation')
        </div>
    </div>
</div>
