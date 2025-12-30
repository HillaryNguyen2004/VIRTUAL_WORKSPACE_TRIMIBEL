@extends('layout_dashboard')
@section('content')
    @vite(['resources/js/settings/upload_image.js'])
    @vite(['resources/js/settings/update_detail.js'])
    <div class="flex flex-col gap-6 w-full h-fit">
        <!-- <a href="{{ route('profile') }}" class="text-[#5D3FD3] text-lg font-medium w-fit">
            &larr; {{ __('settings.back_to_profile') }}
        </a> -->
        <x-back-btn route="profile" />
        <div
            class="flex flex-col items-center w-full h-fit bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:150ms]">
            <div class="w-full py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl relative">
                <h1>{{ __('settings.update_profile_title') }}</h1>
            </div>
            <div class="flex flex-col items-center gap-6 w-full py-6 px-8">
                <!-- form content -->
                <form id="avatar-form" action="{{ route('settings.update.avatar') }}" method="POST"
                    class="flex flex-col items-center gap-6 w-full">
                    @csrf
                    @method('PUT')
                    <!-- top section image -->
                    <div class="w-full flex items-center justify-between">
                        <h1 class="text-md xl:text-lg font-medium">{{ __('settings.avatar_label') }}</h1>
                        <input type="file" name="avatar" id="avatar" accept="image/jpg,image/png,image/jpeg" class="hidden">

                        <button type="button" id="choose-avatar"
                            class="flex items-center justify-center p-2 rounded-full bg-[#5D3FD3] hover:opacity-95 shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                class="w-5 h-5 md:w-6 md:h-6 fill-white">
                                <path
                                    d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Avatar -->
                    <div class="flex flex-col items-center gap-3">
                        <img id="avatar-preview" src="{{ getUserAvatar(Auth::user()) }}"
                            class="w-[150px] h-[150px] md:w-[200px] md:h-[200px] object-cover rounded-full"
                            alt="User Avatar">
                        @if ($errors->has('avatar'))
                            @foreach ($errors->get('avatar') as $error)
                                <span id="error-avatar" class="text-red-400 text-xs">{{ $error }}</span>
                            @endforeach
                        @endif

                        @if (session('success_avatar'))
                            <span class="w-full text-center text-green-600">{{ session('success_avatar') }}</span>
                        @endif
                    </div>

                    <button id="upload-btn" type="submit"
                        class="px-4 py-2 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                        {{ __('settings.upload_image_button') }}
                    </button>
                </form>

                <div class="h-[1px] w-full bg-[#D9D9D9]"></div>

                <form id="detail-form" action="{{ route('settings.update.name') }}" method="POST"
                    class="flex flex-col items-center justify-center gap-6 w-full">
                    @csrf
                    @method('PUT')
                    <div class="w-full">
                        <h1 class="text-md xl:text-lg font-medium">{{ __('settings.detail_label') }}</h1>
                    </div>
                    <div class="flex flex-col md:flex-row items-center gap-5 w-full text-sm xl:text-lg">
                        <!-- First name input -->
                        <div class="flex flex-col gap-3 w-full">
                            <!-- <label for="first_name">{{ __('settings.firstname_label') }}</label> -->
                            <input name="first_name" id="first_name"
                                value="{{ old('first_name', auth()->user()->first_name) }}"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                                placeholder="{{ __('settings.firstname_label') }}">
                            @if ($errors->has('first_name') || $errors->has('last_name'))
                                @foreach ($errors->get('first_name') as $error)
                                    <span id="error-first-name" class="text-red-400 text-xs">{{ $error }}</span>
                                @endforeach
                            @endif
                        </div>

                        <!-- Last name input -->
                        <div class="flex flex-col gap-3 w-full">
                            <!-- <label for="last_name">{{ __('settings.lastname_label') }}</label> -->
                            <input name="last_name" id="last_name" value="{{ old('last_name', auth()->user()->last_name) }}"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                                placeholder="{{ __('settings.lastname_label') }}">
                            @if ($errors->has('first_name') || $errors->has('last_name'))
                                @foreach ($errors->get('last_name') as $error)
                                    <span id="error-last-name" class="text-red-400 text-xs">{{ $error }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    @if (session('success_name'))
                        <span class="w-full text-center text-green-600">{{ session('success_name') }}</span>
                    @endif

                    <button 
                        id="update-detail-btn"
                        type="submit"
                        class="px-4 py-2 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                        {{ __('profile.edit_profile_button') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- <div class="container-fluid">

                                                                <h1 class="h3 mb-4 text-gray-800">Account Settings</h1>

                                                                <div class="row">
                                                                    <div class="col-lg-6">

                                                                        <div class="card shadow mb-4">
                                                                            <div class="card-header py-3">
                                                                                <h6 class="m-0 font-weight-bold text-primary">Update Name</h6>
                                                                            </div>
                                                                            <div class="card-body">

                                                                                {{-- Success Message --}}
                                                                                @if (session('success_name'))
                                                                                    <div class="alert alert-success">
                                                                                        {{ session('success_name') }}
                                                                                    </div>
                                                                                @endif

                                                                                {{-- Error Messages --}}
                                                                                @if ($errors->has('first_name') || $errors->has('last_name'))
                                                                                    <div class="alert alert-danger">
                                                                                        <ul class="mb-0">
                                                                                            @foreach ($errors->get('first_name') as $error)
                                                                                                <li>{{ $error }}</li>
                                                                                            @endforeach
                                                                                            @foreach ($errors->get('last_name') as $error)
                                                                                                <li>{{ $error }}</li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </div>
                                                                                @endif

                                                                                <form method="POST" action="{{ route('settings.update.name') }}">
                                                                                    @csrf
                                                                                    @method('PUT')

                                                                                    <div class="form-group">
                                                                                        <label for="first_name">First Name</label>
                                                                                        <input type="text" name="first_name" id="first_name" class="form-control"
                                                                                               value="{{ old('first_name', auth()->user()->first_name) }}" required>
                                                                                    </div>

                                                                                    <div class="form-group">
                                                                                        <label for="last_name">Last Name</label>
                                                                                        <input type="text" name="last_name" id="last_name" class="form-control"
                                                                                               value="{{ old('last_name', auth()->user()->last_name) }}" required>
                                                                                    </div>

                                                                                    <div class="form-group">
                                                                                        <button type="submit" class="btn btn-primary">
                                                                                            Save Name
                                                                                        </button>
                                                                                    </div>
                                                                                </form>

                                                                            </div>
                                                                        </div>

                                                                        <div class="card shadow mb-4">
                                                                            <div class="card-header py-3">
                                                                                <h6 class="m-0 font-weight-bold text-primary">Update Profile Picture</h6>
                                                                            </div>
                                                                            <div class="card-body">

                                                                                {{-- Success Message --}}
                                                                                @if (session('success_avatar'))
                                                                                    <div class="alert alert-success">
                                                                                        {{ session('success_avatar') }}
                                                                                    </div>
                                                                                @endif

                                                                                {{-- Error Message --}}
                                                                                @if ($errors->has('avatar'))
                                                                                    <div class="alert alert-danger">
                                                                                        <ul class="mb-0">
                                                                                            @foreach ($errors->get('avatar') as $error)
                                                                                                <li>{{ $error }}</li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </div>
                                                                                @endif

                                                                                <form method="POST" action="{{ route('settings.update.avatar') }}" enctype="multipart/form-data">
                                                                                    @csrf
                                                                                    @method('PUT')

                                                                                    <div class="form-group">
                                                                                        <label for="avatar">Profile Photo</label>
                                                                                        <input type="file" name="avatar" id="avatar" accept=".jpg,.jpeg,.png" class="form-control-file">

                                                                                        <div class="mt-2" id="avatarPreviewContainer" style="display: none;"></div>
                                                                                    </div>

                                                                                    <div class="form-group">
                                                                                        <button type="submit" class="btn btn-primary">
                                                                                            Upload Photo
                                                                                        </button>
                                                                                    </div>
                                                                                </form>

                                                                            </div>
                                                                        </div>

                                                                        <div class="text-center">
                                                                            <a href="{{ route('password.request') }}" class="btn btn-outline-secondary">
                                                                                Reset Password
                                                                            </a>
                                                                        </div>

                                                                    </div>
                                                                </div>

                                                            </div> -->
@endsection