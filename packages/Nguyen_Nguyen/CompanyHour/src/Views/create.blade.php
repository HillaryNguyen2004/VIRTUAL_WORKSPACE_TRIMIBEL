@extends('layout_dashboard')
@section('content')
    <x-action-layout :route="'companyhour.index'" :title="'company_hour.back_to_company_hour'">
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
        <x-companyhour::form-action :title="__('company_hour.add_btn')" :formAction="route('companyhour.store')" />
    </x-action-layout>
@endsection