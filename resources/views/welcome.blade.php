@extends('layouts.auth')

@section('content')
<div class="page-single">
    <div class="container">
        <div class="row">
            <div class="col col-login mx-auto">

                <form class="card" action="" method="post">
                    <div class="card-body p-6 text-center">
                        <div class="text-center mb-6">
                            <h3>DriveSaver</h3>
                            <p>Save files from URL to Google Drive</p>
                        </div>
                        <a href="{{ url('auth') }}" class="btn btn-red">
                            <span class="fa fa-google"></span>
                            Login with Google
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
