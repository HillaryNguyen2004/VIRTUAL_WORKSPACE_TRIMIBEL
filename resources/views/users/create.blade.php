@extends('layout_dashboard')

@section('content')
    <x-action-layout :route="'users.index'" :title="'create_user.back_to_user'">
        {{-- success message --}}
        @if(session('success'))
            <div
                class="bg-[#D6F5E3] text-[#5AE194] border border-[#5AE194] text-lg text-center px-3 py-2 rounded-2xl w-full animate-fade-in-up [animation-delay:150ms]">
                {{ session('success') }}
            </div>
        @endif

        {{-- error message --}}
        @if ($errors->any())
            <ul class="flex flex-col gap-2">
                @foreach ($errors->all() as $error)
                    <li
                        class="bg-red-50 text-red-400 border border-red-400 text-lg text-center px-3 py-2 rounded-2xl w-full animate-fade-in-up [animation-delay:150ms]">
                        {{ __('create_user.error_message', ['error' => $error]) }}
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="flex flex-col md:flex-row gap-6 w-full h-fit">
            {{-- add user raw --}}
            <x-form-layout :title="__('create_user.title')">
                {{-- form --}}
                <form action="{{ route('admin.users.store') }}" method="POST"
                    class="flex flex-col items-center gap-3 w-full py-6 px-8">
                    @csrf
                    {{-- name --}}
                    <div class="flex flex-col gap-1 w-full text-sm xl:text-lg">
                        <label class="">{{ __('create_user.name_label') }} <span class="text-red-600">*</span></label>
                        <input type="text" name="name" placeholder="{{ __('create_user.name_label') }}"
                            class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                            value="{{ old('name') }}" required>
                    </div>
                    <div class="flex flex-col gap-1 w-full text-sm xl:text-lg">
                        <label class="">{{ __('create_user.email_label') }} <span class="text-red-600">*</span></label>
                        <input type="email" name="email" placeholder="{{ __('create_user.email_label') }}"
                            class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                            value="{{ old('email') }}" required>
                    </div>
                    <div class="flex flex-col gap-1 w-full text-sm xl:text-lg">
                        <label class="">{{ __('create_user.role_label') }} <span class="text-red-600">*</span></label>
                        <select name="roles"
                            class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                            required>
                            <option value="" disabled selected>{{ __('create_user.select_role') }}</option>
                            <option value="user" {{ old('roles') == 'user' ? 'selected' : '' }}>
                                {{ __('create_user.user_role') }}
                            </option>
                            <option value="staff" {{ old('roles') == 'staff' ? 'selected' : '' }}>
                                {{ __('create_user.staff_role') }}
                            </option>
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 mt-4 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                        {{ __('create_user.submit_button') }}
                    </button>
                </form>
            </x-form-layout>

            {{-- add user import csv --}}
            <x-form-layout :title="__('create_user.import_csv_title')">
                {{-- form --}}
                <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data"
                    class="flex flex-col items-center gap-3 w-full py-6 px-8">
                    @csrf
                    <div class="flex flex-col gap-1 w-full text-sm xl:text-lg">
                        <label class="">{{ __('create_user.csv_label') ?? 'CSV File' }} <span class="text-red-600">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv"
                            class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                            required>
                    </div>
                    <div class="flex flex-col md:flex-row items-center justify-between gap-2 w-full">
                        <button
                            class="flex gap-2 items-center justify-center px-4 py-2 mt-4 w-full sm:w-fit bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 640 640"
                                class="w-5 h-5 fill-white"
                            >
                                <path
                                    d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z" />
                            </svg>
                            {{ __('create_user.import_button') ?? 'Import' }}
                        </button>
                        <a href="{{ route('admin.users.import.template') }}"
                            class="flex gap-2 items-center justify-center px-4 py-2 mt-4 w-full sm:w-fit bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-white">
                                <path
                                    d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z" />
                            </svg>
                            {{ __('create_user.download_template') ?? 'Download Template' }}
                        </a>
                    </div>
                </form>
            </x-form-layout>
        </div>
    </x-action-layout>

    <!-- <div class="container py-4">
                                                {{-- Page Title --}}
                                                <h1 class="mb-4 fw-bold text-center">{{ __('create_user.title') }}</h1>

                                                {{-- Success Message --}}
                                                @if (session('success'))
                                                    <div class="alert alert-success text-center">{{ session('success') }}</div>
                                                @endif

                                                {{-- Error Message --}}
                                                @if ($errors->any())
                                                    <div class="alert alert-danger">
                                                        <ul class="mb-0">
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ __('create_user.error_message', ['error' => $error]) }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                <div class="row">
                                                    {{-- Create New User --}}
                                                    <div class="col-lg-6 mb-4">
                                                        <div class="card shadow-sm h-100">
                                                            <div class="card-header bg-primary text-white fw-bold">
                                                                {{ __('create_user.create_new_user') }}
                                                            </div>
                                                            <div class="card-body">
                                                                <form action="{{ route('admin.users.store') }}" method="POST">
                                                                    @csrf
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">{{ __('create_user.name_label') }}</label>
                                                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">{{ __('create_user.email_label') }}</label>
                                                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">{{ __('create_user.role_label') }}</label>
                                                                        <select name="roles" class="form-select" required>
                                                                            <option value="" disabled selected>{{ __('create_user.select_role') }}</option>
                                                                            <option value="user" {{ old('roles') == 'user' ? 'selected' : '' }}>{{ __('create_user.user_role') }}</option>
                                                                            <option value="staff" {{ old('roles') == 'staff' ? 'selected' : '' }}>{{ __('create_user.staff_role') }}</option>
                                                                        </select>
                                                                    </div>
                                                                    <button class="btn btn-success w-100">
                                                                        <i class="bi bi-person-plus"></i> {{ __('create_user.submit_button') }}
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Import Users from CSV --}}
                                                    <div class="col-lg-6 mb-4">
                                                        <div class="card shadow-sm h-100">
                                                            <div class="card-header bg-secondary text-white fw-bold">
                                                                {{ __('create_user.import_csv_title') ?? 'Import Users from CSV' }}
                                                            </div>
                                                            <div class="card-body">
                                                                <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                                                                    @csrf
                                                                    <div class="mb-3">
                                                                        <label class="form-label">{{ __('create_user.csv_label') ?? 'CSV File' }}</label>
                                                                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between">
                                                                        <button class="btn btn-primary">
                                                                            <i class="bi bi-upload"></i> {{ __('create_user.import_button') ?? 'Import' }}
                                                                        </button>
                                                                        <a href="{{ route('admin.users.import.template') }}" class="btn btn-outline-secondary">
                                                                            <i class="bi bi-download"></i> {{ __('create_user.download_template') ?? 'Download Template' }}
                                                                        </a>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> -->
@endsection