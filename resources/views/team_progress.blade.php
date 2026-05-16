{{-- resources/views/team-progress.blade.php --}}
@extends('layout_dashboard')

@section('title', 'Team Progress Tracking')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Team Progress Tracking</h1>
        <p class="text-gray-600 mt-2">Monitor your team members' progress and task completion</p>
    </div>

    <!-- Team Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Tasks -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Tasks</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $teamStats['total_tasks'] }}</p>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Completed</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $teamStats['completed_tasks'] }}</p>
                </div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">In Progress</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $teamStats['in_progress_tasks'] }}</p>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Pending</h3>
                    <p class="text-2xl font-semibold text-gray-900">{{ $teamStats['pending_tasks'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members Progress -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-800">Team Members Progress</h2>
            <p class="text-gray-600 mt-1">Team Leader:
                <span class="font-semibold">
                    {{ $currentUser->team_leader_id
                        ? optional(\App\Models\User::find($currentUser->team_leader_id))->name ?? 'N/A'
                        : 'You are the team leader' }}
                </span>
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Tasks</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($teamUsers as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <x-user-avatar :user="$user" size="h-10 w-10" :withRing="false" />
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->username }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-green-600">{{ $user->completed_tasks_count }}</span> /
                            <span class="text-gray-800">{{ $user->total_tasks_count }}</span>
                            <span class="text-gray-500">completed</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="h-2 rounded-full 
                                        @if($user->completion_rate >= 80) bg-green-500
                                        @elseif($user->completion_rate >= 50) bg-yellow-500
                                        @elseif($user->completion_rate > 0) bg-orange-500
                                        @else bg-gray-300
                                        @endif"
                                        style="width: {{ $user->completion_rate }}%">
                                    </div>
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ $user->completion_rate }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full
                                @if($user->status === 'active') bg-green-100 text-green-800
                                @elseif($user->status === 'busy') bg-yellow-100 text-yellow-800
                                @elseif($user->status === 'overdue') bg-red-100 text-red-800
                                @elseif($user->status === 'needs_help') bg-orange-100 text-orange-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $user->status)) }}
                            </span>
                        </td>
                        <!-- ✅ Fixed Action Buttons -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if(auth()->user()->hasRole('staff'))
                                {{-- Staff viewing --}}
                                @if($user->hasRole('user'))
                                    <!-- Staff can view & assign users -->
                                    <button onclick="viewUserTasks({{ $user->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                        View Tasks
                                    </button>
                                    <a href="{{ route('team.overview', ['user_id' => $user->id]) }}" 
                                    class="text-indigo-600 hover:text-indigo-900">
                                        Assign Task
                                    </a>
                                @elseif($user->hasRole('staff'))
                                    <!-- Staff can only view other staff -->
                                    <button onclick="viewUserTasks({{ $user->id }})" class="text-blue-600 hover:text-blue-900">
                                        View Tasks
                                    </button>
                                @else
                                    <span class="text-gray-500 italic">No actions available</span>
                                @endif

                            @elseif(auth()->user()->hasRole('user'))
                                {{-- User viewing (can only view) --}}
                                <button onclick="viewUserTasks({{ $user->id }})" class="text-blue-600 hover:text-blue-900">
                                    View Tasks
                                </button>
                            @else
                                <span class="text-gray-500 italic">No actions available</span>
                            @endif
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            No team members found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Team Activity -->
    <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-800">Recent Team Activity</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @php
                $recentActivities = \App\Models\Task::whereHas('users', function($query) use ($currentUser) {
                    $teamLeaderId = $currentUser->team_leader_id ?? $currentUser->id;
                    $query->whereIn('users.id',
                        \App\Models\User::where('team_leader_id', $teamLeaderId)
                            ->orWhere('id', $teamLeaderId)
                            ->pluck('id')
                    );
                })
                ->with('users')
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
            @endphp

            @foreach($recentActivities as $task)
            <div class="px-6 py-4 flex items-center">
                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <span class="text-blue-600 text-sm font-semibold">
                        {{ substr($task->users->first()->name ?? 'U', 0, 1) }}
                    </span>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm text-gray-900">
                        <span class="font-semibold">{{ $task->users->first()->name ?? 'Unknown User' }}</span>
                        updated task <span class="font-medium">"{{ $task->title }}"</span> to
                        <span class="font-semibold
                            @if($task->status === 'completed') text-green-600
                            @elseif($task->status === 'in_progress') text-yellow-600
                            @else text-gray-600
                            @endif">
                            {{ $task->status }}
                        </span>
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ $task->updated_at ? $task->updated_at->diffForHumans() : 'No update time' }}
                    </p>
                </div>
                @if($task->percentage > 0)
                <div class="ml-4 text-sm font-medium text-gray-900">{{ $task->percentage }}%</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
async function viewUserTasks(userId) {
    // Fetch tasks for this user
    try {
        const response = await fetch(`/user-tasks/${userId}`);
        const tasks = await response.json();

        // If no tasks found
        if (tasks.length === 0) {
            showTaskDropdown(userId, []);
            return;
        }

        showTaskDropdown(userId, tasks);
    } catch (error) {
        console.error('Error fetching user tasks:', error);
        alert('Could not load tasks.');
    }
}

function showTaskDropdown(userId, tasks) {
    // Remove any existing dropdown
    const existing = document.getElementById('taskDropdown');
    if (existing) existing.remove();

    // Create dropdown container
    const dropdown = document.createElement('div');
    dropdown.id = 'taskDropdown';
    dropdown.className = 'fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50';

    // Build content
    const content = document.createElement('div');
    content.className = 'bg-white rounded-xl shadow-lg p-6 w-96';

    content.innerHTML = `
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Tasks Assigned to User #${userId}</h2>
        ${tasks.length > 0
            ? `
            <select class="w-full border rounded-lg p-2 text-gray-700 mb-4">
                ${tasks.map(task => `
                    <option value="${task.id}">
                        ${task.title} — ${task.status} (Due: ${task.due_date || 'N/A'})
                    </option>
                `).join('')}
            </select>
            `
            : `<p class="text-gray-600 mb-4">No tasks assigned.</p>`
        }
        <div class="flex justify-end">
            <button onclick="document.getElementById('taskDropdown').remove()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Close
            </button>
        </div>
    `;

    dropdown.appendChild(content);
    document.body.appendChild(dropdown);
}
</script>

@endsection
