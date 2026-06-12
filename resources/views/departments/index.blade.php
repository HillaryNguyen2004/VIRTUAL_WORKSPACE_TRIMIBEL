@extends('layout_dashboard')
@section('title', 'Quản lý phòng ban')

@section('content')
    @php
        $canEditDepartments = auth()->user()->can('admin.departments.edit');
    @endphp
    <div class="flex flex-col gap-6 w-full max-w-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex items-center">
            <x-back-btn></x-back-btn>
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">Quản lý phòng ban</h2>
                <p class="text-muted-500 text-sm mt-1">Tạo phòng ban và gán nhân viên (staff) vào phòng ban.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="flex items-center gap-3 bg-accent/10 border border-accent/20 text-accent p-4 rounded-xl">
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="flex items-center gap-3 bg-danger/10 border border-danger/20 text-danger p-4 rounded-xl">
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="flex flex-col gap-2">
                @foreach ($errors->all() as $error)
                    <div class="bg-danger/10 border border-danger/20 text-danger p-3 rounded-lg text-sm">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @can('admin.departments.create')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
                <h3 class="font-semibold text-lg mb-4">Tạo phòng ban</h3>
                <form method="POST" action="{{ route('admin.departments.store') }}" class="flex flex-col gap-4 md:flex-row md:items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-main mb-2" for="department_name">Tên phòng ban</label>
                        <input id="department_name" name="name" type="text" class="block w-full bg-canvas border text-main h-[50px] px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="VD: HR" required>
                    </div>
                    <button type="submit" class="h-[50px] px-6 rounded-xl bg-primary text-white font-semibold">Tạo phòng ban</button>
                </form>
            </div>
        @endcan

        <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
            <h3 class="font-semibold text-lg mb-4">Danh sách phòng ban</h3>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-muted-500">
                        <tr>
                            <th class="py-3 pr-4">Phòng ban</th>
                            <th class="py-3 pr-4">Nhân viên (staff)</th>
                            @can('admin.departments.edit')
                                <th class="py-3 pr-4">Gán nhân viên</th>
                            @endcan
                            @can('admin.deparments.delete')
                                <th class="py-3">Thao tác</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted-200">
                        @foreach($departments as $department)
                            <tr>
                                <td class="py-4 pr-4">
                                    <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" type="text" value="{{ $department->name }}" class="w-full bg-canvas border text-main h-[42px] px-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required @disabled(!$canEditDepartments)>
                                        @can('admin.departments.edit')
                                            <button type="submit" class="px-3 h-[42px] rounded-lg bg-secondary text-white">Lưu</button>
                                        @endcan
                                    </form>
                                </td>
                                <td class="py-4 pr-4">
                                    @if($department->users->isEmpty())
                                        <span class="text-muted-500">Chưa có nhân viên</span>
                                    @else
                                        <ul class="flex flex-col gap-2">
                                            @foreach($department->users as $user)
                                                <li class="flex items-center justify-between gap-3">
                                                    <span>{{ $user->name }} <span class="text-muted-400">({{ $user->email }})</span></span>
                                                    @can('admin.departments.edit')
                                                        <form method="POST" action="{{ route('admin.departments.remove', [$department, $user]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-danger text-xs">Gỡ</button>
                                                        </form>
                                                    @endcan
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                                @can('admin.departments.edit')
                                    <td class="py-4 pr-4">
                                        <form method="POST" action="{{ route('admin.departments.assign', $department) }}" class="flex items-center gap-2">
                                            @csrf
                                            <select name="user_id" class="bg-canvas border text-main h-[42px] px-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                                                <option value="" disabled selected>Chọn nhân viên</option>
                                                @foreach($availableUsers as $user)
                                                    <option value="{{ $user->id }}">
                                                        {{ $user->name }}
                                                        @php
                                                            $roles = $user->getRoleNames()->join(', ');
                                                        @endphp
                                                        ({{ $roles ?: 'No role' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="px-3 h-[42px] rounded-lg bg-primary text-white">Gán</button>
                                        </form>
                                    </td>
                                @endcan
                                @can('admin.departments.delete')
                                    <td class="py-4">
                                        <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Xóa phòng ban này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 h-[42px] rounded-lg bg-danger text-white">Xóa</button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
