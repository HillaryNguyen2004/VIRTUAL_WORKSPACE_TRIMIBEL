@extends('layout_dashboard')
@section('title', 'API Debug')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Metered API Debug</h1>
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">API Configuration</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Base URL</label>
                <p class="mt-1 text-sm text-gray-900">https://manageuservn.metered.live/api/v1</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">API Key Set</label>
                <p class="mt-1 text-sm {{ env('METERED_API_KEY') ? 'text-green-600' : 'text-red-600' }}">
                    {{ env('METERED_API_KEY') ? 'Yes' : 'No' }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">User ID</label>
                <p class="mt-1 text-sm text-gray-900">{{ Auth::id() }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">User Email</label>
                <p class="mt-1 text-sm text-gray-900">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Test API Calls</h2>
            
            <div class="space-y-4">
                <button onclick="testRoomsAPI()" 
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    Test /rooms Endpoint
                </button>
                
                <button onclick="testUserMeetings()" 
                        class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    Get My Meetings
                </button>
                
                <button onclick="testSessionAPI()" 
                        class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    Test Sessions Endpoint
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">API Response</h2>
            <div id="apiResponse" class="bg-gray-50 p-4 rounded h-64 overflow-auto text-sm">
                <p class="text-gray-500">Click a button to test API...</p>
            </div>
        </div>
    </div>
    
    <div id="meetingsList" class="mt-6 bg-white rounded-lg shadow p-6">
        <!-- Meetings will be loaded here -->
    </div>
</div>

<script>
function testRoomsAPI() {
    showLoading('Fetching rooms...');
    
    fetch('/meetings/debug/api')
        .then(response => response.json())
        .then(data => {
            displayResponse(data);
        })
        .catch(error => {
            displayResponse({ error: error.message });
        });
}

function testUserMeetings() {
    showLoading('Fetching your meetings...');
    
    fetch('/meetings/sync')
        .then(response => response.json())
        .then(data => {
            displayResponse(data);
            displayMeetings(data.meetings);
        })
        .catch(error => {
            displayResponse({ error: error.message });
        });
}

function testSessionAPI() {
    const roomName = prompt('Enter room name to test sessions:');
    if (!roomName) return;
    
    showLoading(`Fetching sessions for room: ${roomName}`);
    
    fetch(`/api/meetings/${roomName}/details`)
        .then(response => response.json())
        .then(data => {
            displayResponse(data);
        })
        .catch(error => {
            displayResponse({ error: error.message });
        });
}

function showLoading(message) {
    document.getElementById('apiResponse').innerHTML = 
        `<div class="flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
            <span>${message}</span>
        </div>`;
}

function displayResponse(data) {
    const responseDiv = document.getElementById('apiResponse');
    responseDiv.innerHTML = `<pre class="text-xs">${JSON.stringify(data, null, 2)}</pre>`;
}

function displayMeetings(meetings) {
    const container = document.getElementById('meetingsList');
    
    if (!meetings || meetings.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No meetings found.</p>';
        return;
    }
    
    let html = '<h3 class="text-lg font-semibold mb-4">Your Meetings</h3>';
    html += '<div class="space-y-4">';
    
    meetings.forEach(meeting => {
        html += `
            <div class="border rounded p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-medium">${meeting.room_name}</h4>
                        <p class="text-sm text-gray-600">
                            ${new Date(meeting.start_time).toLocaleString()}
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded ${meeting.is_local ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                        ${meeting.is_local ? 'Local DB' : 'API'}
                    </span>
                </div>
                <div class="mt-2 text-sm">
                    <p><strong>Duration:</strong> ${meeting.duration} minutes</p>
                    <p><strong>Participants:</strong> ${meeting.attendees_count}</p>
                    <p><strong>Status:</strong> ${meeting.status}</p>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}
</script>
@endsection