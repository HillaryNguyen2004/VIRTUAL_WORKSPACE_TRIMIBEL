@extends('layout_dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">📊 Team KPI Dashboard by Project</h2>

    @foreach($projects as $projectData)
    <div class="bg-white rounded-xl shadow-md mb-6 overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h4 class="text-xl font-bold mb-0">
                        {{ $projectData['project']->title }}
                    </h4>
                    @if($projectData['project']->start_date && $projectData['project']->due_date)
                    <p class="text-blue-100 text-sm mt-1">
                        📅 {{ $projectData['project']->start_date }} → {{ $projectData['project']->due_date }}
                    </p>
                    @endif
                </div>
                <span class="bg-white text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                    {{ round($projectData['project_completion'], 1) }}% Complete
                </span>
            </div>
            <p class="text-blue-100 mt-1">
                {{ $projectData['completed_tasks'] }}/{{ $projectData['total_tasks'] }} tasks completed
            </p>
        </div>
        
        <div class="p-6">
            <!-- BAR CHART - Team Member Comparison -->
            <div class="mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <h5 class="text-lg font-semibold mb-4 text-gray-700">
                        📈 Team Performance Comparison - {{ $projectData['project']->title }}
                    </h5>
                    <div class="h-32">
                        <canvas id="barChart_{{ $projectData['project']->id }}"></canvas>
                    </div>
                </div>
            </div>

            <!-- PROGRESS BARS - Individual Member Details -->
            <div class="mt-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-3 border-b">
                        <div class="flex justify-between items-center">
                            <h5 class="text-lg font-semibold text-gray-700">👥 Individual Member Progress</h5>
                            <div class="text-sm text-gray-600">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                    Project: {{ $projectData['project']->title }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        @foreach($projectData['team_members'] as $memberId => $member)
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center">
                                    <strong class="text-gray-800">{{ $member['user']['name'] }}</strong>
                                    <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                        {{ $member['completed_tasks'] }}/{{ $member['total_tasks'] }} tasks
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <strong class="{{ $member['completion_percentage'] >= 80 ? 'text-green-600' : ($member['completion_percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600') }} text-lg">
                                        {{ $member['completion_percentage'] }}%
                                    </strong>
                                    @if($member['avg_in_progress_percentage'] > 0)
                                    <span class="ml-2 text-sm text-gray-500">
                                        (Avg in progress: {{ $member['avg_in_progress_percentage'] }}%)
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-6">
                                @php
                                    $percentage = $member['completion_percentage'];
                                    $color = $percentage >= 80 ? 'bg-green-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                                @endphp
                                <div class="{{ $color }} h-6 rounded-full relative transition-all duration-500 ease-out"
                                     style="width: {{ $percentage }}%">
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-semibold text-white">{{ $member['completion_percentage'] }}%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-1"></span>
                                    Assigned: {{ $member['total_tasks'] }}
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
                                    Completed: {{ $member['completed_tasks'] }}
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-yellow-500 rounded-full mr-1"></span>
                                    In Progress: {{ $member['in_progress_tasks'] }}
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-gray-400 rounded-full mr-1"></span>
                                    Pending: {{ $member['pending_tasks'] }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        @if(empty($projectData['team_members']))
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-blue-800">
                            No team members assigned to tasks in this project yet.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- RADAR CHART SECTION - Individual Member Performance Across Projects -->
    @if(!empty($member_performance))
    <div class="bg-white rounded-xl shadow-md mb-6 overflow-hidden">
        <div class="bg-blue-500 text-white px-6 py-4">
            <h4 class="text-xl font-bold mb-0">🎯 Individual Member Performance Across Projects</h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($member_performance as $memberId => $memberData)
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="border-b pb-3 mb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h5 class="text-lg font-semibold text-gray-800">{{ $memberData['user']['name'] }} - Performance Radar</h5>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $memberData['active_projects'] }} projects, {{ $memberData['total_task_count'] }} total tasks
                                </p>
                            </div>
                            @if($memberData['user']['assigned_projects'] ?? false)
                            <div class="text-right">
                                <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">
                                    Active Projects: {{ $memberData['active_projects'] }}
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="h-64">
                        <canvas id="radarChart_{{ $memberId }}"></canvas>
                    </div>
                    
                    <!-- Performance Metrics -->
                    <div class="grid grid-cols-4 gap-2 mt-4">
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <h6 class="font-medium text-gray-700 text-sm mb-1">Completion</h6>
                            <span class="inline-block bg-green-100 text-green-800 text-sm font-semibold px-2 py-1 rounded-full">
                                {{ $memberData['overall_completion'] }}%
                            </span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <h6 class="font-medium text-gray-700 text-sm mb-1">Progress</h6>
                            <span class="inline-block bg-blue-100 text-blue-800 text-sm font-semibold px-2 py-1 rounded-full">
                                {{ $memberData['overall_progress'] }}%
                            </span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <h6 class="font-medium text-gray-700 text-sm mb-1">On Time</h6>
                            <span class="inline-block bg-yellow-100 text-yellow-800 text-sm font-semibold px-2 py-1 rounded-full">
                                {{ $memberData['overall_on_time'] }}%
                            </span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <h6 class="font-medium text-gray-700 text-sm mb-1">Quality</h6>
                            <span class="inline-block bg-indigo-100 text-indigo-800 text-sm font-semibold px-2 py-1 rounded-full">
                                {{ $memberData['overall_quality'] }}%
                            </span>
                        </div>
                    </div>
                    
                    <!-- Project-wise Breakdown with improved toggle -->
                    <div class="mt-6">
                        <button onclick="toggleProjectDetails('{{ $memberId }}')" 
                                id="toggleBtn_{{ $memberId }}"
                                class="flex items-center justify-between w-full px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors duration-200">
                            <span class="font-medium">View Project Details</span>
                            <svg id="icon_{{ $memberId }}" class="w-5 h-5 transform transition-transform duration-200" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div id="projectDetails_{{ $memberId }}" 
                             class="mt-3 overflow-hidden transition-all duration-300 ease-in-out max-h-0">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="mb-3">
                                    <h6 class="font-medium text-gray-700 text-sm mb-1">Projects Assigned to {{ $memberData['user']['name'] }}:</h6>
                                </div>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th class="px-3 py-2">Project Name</th>
                                            <th class="px-3 py-2">Period</th>
                                            <th class="px-3 py-2">Completion</th>
                                            <th class="px-3 py-2">Progress</th>
                                            <th class="px-3 py-2">On Time</th>
                                            <th class="px-3 py-2">Tasks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($memberData['projects'] as $projectId => $project)
                                        @php
                                            // Get project dates from the original projects array
                                            $projectDates = '';
                                            foreach ($projects as $proj) {
                                                if ($proj['project']->id == $projectId && $proj['project']->start_date && $proj['project']->due_date) {
                                                    $projectDates = $proj['project']->start_date . ' → ' . $proj['project']->due_date;
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-100">
                                            <td class="px-3 py-2 text-sm text-gray-800">
                                                <div class="font-medium">{{ $project['project_name'] }}</div>
                                                @if($projectDates)
                                                <div class="text-xs text-gray-500 mt-1">📅 {{ $projectDates }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-600">
                                                @if($projectDates)
                                                <span class="text-xs">{{ $projectDates }}</span>
                                                @else
                                                <span class="text-xs text-gray-400">No dates</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="inline-block w-12 text-center bg-green-50 text-green-700 px-2 py-1 rounded text-xs font-medium">
                                                    {{ $project['completion_rate'] }}%
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="inline-block w-12 text-center bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-medium">
                                                    {{ $project['avg_progress'] }}%
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="inline-block w-12 text-center bg-yellow-50 text-yellow-700 px-2 py-1 rounded text-xs font-medium">
                                                    {{ $project['on_time_rate'] }}%
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-700 text-center font-medium">
                                                {{ $project['task_count'] }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                
                                <!-- Summary -->
                                @if(count($memberData['projects']) > 0)
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="grid grid-cols-4 gap-4 text-center">
                                        <div>
                                            <p class="text-xs text-gray-500">Total Projects</p>
                                            <p class="font-bold text-gray-800">{{ count($memberData['projects']) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Total Tasks</p>
                                            <p class="font-bold text-gray-800">{{ $memberData['total_task_count'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Avg Completion</p>
                                            <p class="font-bold text-green-600">{{ $memberData['overall_completion'] }}%</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Avg On-Time</p>
                                            <p class="font-bold text-yellow-600">{{ $memberData['overall_on_time'] }}%</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Projects Summary Section -->
    @if(count($projects) > 0)
    <div class="bg-white rounded-xl shadow-md mb-6 overflow-hidden">
        <div class="bg-gray-800 text-white px-6 py-4">
            <h4 class="text-xl font-bold mb-0">📋 Projects Summary</h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($projects as $projectData)
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex justify-between items-start mb-3">
                        <h5 class="font-bold text-gray-800">{{ $projectData['project']->title }}</h5>
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">
                            {{ round($projectData['project_completion'], 1) }}%
                        </span>
                    </div>
                    
                    @if($projectData['project']->start_date && $projectData['project']->due_date)
                    <div class="mb-3">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">📅 Period:</span> 
                            {{ $projectData['project']->start_date }} → {{ $projectData['project']->due_date }}
                        </p>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">👥 Team Members:</span> 
                            {{ count($projectData['team_members']) }}
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">✅ Tasks:</span> 
                            {{ $projectData['completed_tasks'] }}/{{ $projectData['total_tasks'] }} completed
                        </p>
                    </div>
                    
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" 
                             style="width: {{ round($projectData['project_completion'], 1) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Overall Stats -->
            @if(count($projects) > 1)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h5 class="font-bold text-gray-800 mb-3">📊 Overall Statistics</h5>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg border text-center">
                        <p class="text-2xl font-bold text-blue-600">{{ count($projects) }}</p>
                        <p class="text-sm text-gray-600">Total Projects</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border text-center">
                        @php
                            $totalTasks = array_sum(array_column($projects, 'total_tasks'));
                            $completedTasks = array_sum(array_column($projects, 'completed_tasks'));
                            $overallCompletion = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
                        @endphp
                        <p class="text-2xl font-bold text-green-600">{{ $overallCompletion }}%</p>
                        <p class="text-sm text-gray-600">Overall Completion</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border text-center">
                        @php
                            $totalMembers = 0;
                            foreach ($projects as $project) {
                                $totalMembers += count($project['team_members']);
                            }
                        @endphp
                        <p class="text-2xl font-bold text-purple-600">{{ $totalMembers }}</p>
                        <p class="text-sm text-gray-600">Total Team Members</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border text-center">
                        @php
                            $avgCompletion = count($projects) > 0 ? 
                                round(array_sum(array_column($projects, 'project_completion')) / count($projects), 1) : 0;
                        @endphp
                        <p class="text-2xl font-bold text-yellow-600">{{ $avgCompletion }}%</p>
                        <p class="text-sm text-gray-600">Avg Project Completion</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if(count($projects) === 0)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
        <p class="text-blue-800">No projects found for your team.</p>
    </div>
    @endif
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Register plugin
    Chart.register(ChartDataLabels);

    @foreach($projects as $projectData)
    {
        // BAR CHART - Team Comparison (per project)
        let projectId = {{ $projectData['project']->id }};
        const members = {!! json_encode($projectData['team_members']) !!};
    
    // Extract member names and percentages for bar chart
    const memberNames = [];
    const memberPercentages = [];
    const memberColors = [];
    const memberTaskCounts = [];
    
    Object.values(members).forEach(member => {
        memberNames.push(member.user.name.substring(0, 15) + (member.user.name.length > 15 ? '...' : ''));
        const percentage = member.completion_percentage;
        memberPercentages.push(percentage);
        memberTaskCounts.push(member.total_tasks);
        
        // Color coding based on percentage
        if (percentage >= 80) {
            memberColors.push('#10b981'); // Green
        } else if (percentage >= 50) {
            memberColors.push('#f59e0b'); // Yellow
        } else {
            memberColors.push('#ef4444'); // Red
        }
    });

    // BAR CHART - Team Comparison
    const barCtx = document.getElementById('barChart_' + projectId).getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: memberNames,
            datasets: [{
                label: 'Completion %',
                data: memberPercentages,
                backgroundColor: memberColors,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                borderRadius: 5,
                yAxisID: 'y',
            }, {
                label: 'Task Count',
                data: memberTaskCounts,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 5,
                type: 'line',
                yAxisID: 'y1',
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                const member = Object.values(members)[context.dataIndex];
                                return [
                                    `Completion: ${context.raw}%`,
                                    `Tasks: ${member.completed_tasks}/${member.total_tasks}`,
                                    `In Progress: ${member.in_progress_tasks} tasks`
                                ];
                            } else {
                                return `Task Count: ${context.raw}`;
                            }
                        }
                    }
                },
                datalabels: {
                    display: function(context) {
                        return context.datasetIndex === 0;
                    },
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return value + '%';
                    },
                    color: '#333',
                    font: {
                        weight: 'bold',
                        size: 11
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Completion (%)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Task Count'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                },
                x: {
                    title: {
                        display: true,
                        text: 'Team Members'
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    }
    @endforeach

    // RADAR CHARTS - Individual Member Performance
    @foreach($member_performance as $memberId => $memberData)
    {
        const radarCtx = document.getElementById('radarChart_{{ $memberId }}');
        
        if (radarCtx) {
            const radarCtx2d = radarCtx.getContext('2d');
            
            const radarData = {
                labels: ['Completion Rate', 'Progress Speed', 'On-Time Delivery', 'Task Quality'],
                datasets: [{
                    label: 'Performance Metrics',
                    data: [
                        {{ $memberData['overall_completion'] }},
                        {{ $memberData['overall_progress'] }},
                        {{ $memberData['overall_on_time'] }},
                        {{ $memberData['overall_quality'] }}
                    ],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgb(59, 130, 246)',
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(59, 130, 246)'
                }]
            };
            
            new Chart(radarCtx2d, {
                type: 'radar',
                data: radarData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 100,
                            ticks: {
                                stepSize: 20,
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            pointLabels: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    @endforeach
});

// Toggle project details with smooth animation
function toggleProjectDetails(memberId) {
    const detailsElement = document.getElementById('projectDetails_' + memberId);
    const iconElement = document.getElementById('icon_' + memberId);
    const toggleBtn = document.getElementById('toggleBtn_' + memberId);
    
    if (detailsElement.classList.contains('max-h-0')) {
        // Open the details
        detailsElement.classList.remove('max-h-0');
        detailsElement.classList.add('max-h-[800px]');
        iconElement.classList.add('rotate-180');
        toggleBtn.innerHTML = toggleBtn.innerHTML.replace('View', 'Hide');
    } else {
        // Close the details
        detailsElement.classList.remove('max-h-[800px]');
        detailsElement.classList.add('max-h-0');
        iconElement.classList.remove('rotate-180');
        toggleBtn.innerHTML = toggleBtn.innerHTML.replace('Hide', 'View');
    }
}

// Close dropdown when clicking outside (optional)
document.addEventListener('click', function(event) {
    if (!event.target.closest('[id^="toggleBtn_"]') && !event.target.closest('[id^="projectDetails_"]')) {
        // Find all open dropdowns and close them
        document.querySelectorAll('[id^="projectDetails_"]').forEach(details => {
            const memberId = details.id.split('_')[1];
            const iconElement = document.getElementById('icon_' + memberId);
            const toggleBtn = document.getElementById('toggleBtn_' + memberId);
            
            if (!details.classList.contains('max-h-0')) {
                details.classList.remove('max-h-[800px]');
                details.classList.add('max-h-0');
                if (iconElement) iconElement.classList.remove('rotate-180');
                if (toggleBtn) toggleBtn.innerHTML = toggleBtn.innerHTML.replace('Hide', 'View');
            }
        });
    }
});
</script>

<style>
/* Custom progress bar animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Smooth transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Ensure table is properly contained */
.table-responsive {
    overflow-x: auto;
}

/* Custom scrollbar for dropdown content */
#projectDetails_::-webkit-scrollbar {
    width: 6px;
}

#projectDetails_::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#projectDetails_::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#projectDetails_::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Project card hover effects */
.bg-gray-50.rounded-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    table {
        font-size: 0.75rem;
    }
    
    .px-3 {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
}
</style>

<!-- Optional: Add Font Awesome via CDN if needed -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endsection