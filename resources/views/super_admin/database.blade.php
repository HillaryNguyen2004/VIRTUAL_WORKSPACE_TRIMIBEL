@extends('layout_dashboard')

@section('title', 'Database Status')

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">Database Status</h1>
            <p class="text-muted-500 text-sm md:text-base">Current database connectivity and table sizes.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Driver</div>
                <div class="text-lg font-semibold text-main">{{ $connectionInfo['driver'] }}</div>
            </div>
            <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Host</div>
                <div class="text-lg font-semibold text-main">{{ $connectionInfo['host'] }}</div>
            </div>
            <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Connections</div>
                <div class="text-lg font-semibold text-main">{{ $connectionInfo['pool'] }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted-500">
                            <th class="py-2 px-3 font-medium">Table</th>
                            <th class="py-2 px-3 font-medium">Rows</th>
                            <th class="py-2 px-3 font-medium">Size</th>
                            <th class="py-2 px-3 font-medium">Engine</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted-200">
                        @foreach($tableStats as $table)
                            <tr>
                                <td class="py-2 px-3 font-medium text-main">{{ $table['name'] }}</td>
                                <td class="py-2 px-3 text-muted-600">{{ $table['rows'] }}</td>
                                <td class="py-2 px-3 text-muted-600">{{ $table['size'] }}</td>
                                <td class="py-2 px-3 text-muted-600">{{ $table['engine'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
