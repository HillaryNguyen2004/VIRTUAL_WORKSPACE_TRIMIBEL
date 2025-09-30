@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Chat Debug for macOS</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Authentication Test</div>
                <div class="card-body">
                    <p><strong>User:</strong> {{ auth()->check() ? auth()->user()->name : 'Not authenticated' }}</p>
                    <p><strong>User ID:</strong> {{ auth()->check() ? auth()->user()->id : 'N/A' }}</p>
                    <p><strong>CSRF Token:</strong> <span id="csrf-token">{{ csrf_token() }}</span></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">API Test</div>
                <div class="card-body">
                    <button class="btn btn-primary" onclick="testAPI()">Test API Connection</button>
                    <div id="api-result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">JavaScript Console</div>
                <div class="card-body">
                    <textarea id="console-log" rows="10" class="form-control" readonly></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">System Information</div>
                <div class="card-body">
                    <p><strong>User Agent:</strong> <span id="user-agent"></span></p>
                    <p><strong>Current URL:</strong> <span id="current-url"></span></p>
                    <p><strong>Protocol:</strong> <span id="protocol"></span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Setup axios defaults
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Console logging
const consoleLog = document.getElementById('console-log');
const originalLog = console.log;
const originalError = console.error;

console.log = function(...args) {
    originalLog.apply(console, args);
    consoleLog.value += '[LOG] ' + args.join(' ') + '\n';
    consoleLog.scrollTop = consoleLog.scrollHeight;
};

console.error = function(...args) {
    originalError.apply(console, args);
    consoleLog.value += '[ERROR] ' + args.join(' ') + '\n';
    consoleLog.scrollTop = consoleLog.scrollHeight;
};

// System info
document.getElementById('user-agent').textContent = navigator.userAgent;
document.getElementById('current-url').textContent = window.location.href;
document.getElementById('protocol').textContent = window.location.protocol;

console.log('Debug page loaded');
console.log('Current user ID:', '{{ auth()->check() ? auth()->user()->id : "null" }}');

async function testAPI() {
    const resultDiv = document.getElementById('api-result');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div> Testing...';
    
    try {
        console.log('Getting CSRF cookie...');
        await axios.get('/api/sanctum/csrf-cookie');
        console.log('CSRF cookie obtained');
        
        console.log('Testing conversations API...');
        const response = await axios.get('/api/chat/conversations');
        console.log('API Response:', response.data);
        
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <h6>✅ API Test Successful</h6>
                <p><strong>Status:</strong> ${response.status}</p>
                <p><strong>Data:</strong> ${JSON.stringify(response.data, null, 2)}</p>
            </div>
        `;
    } catch (error) {
        console.error('API Test Failed:', error);
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6>❌ API Test Failed</h6>
                <p><strong>Error:</strong> ${error.message}</p>
                <p><strong>Status:</strong> ${error.response?.status || 'Unknown'}</p>
                <p><strong>Response:</strong> ${JSON.stringify(error.response?.data, null, 2) || 'No response'}</p>
            </div>
        `;
    }
}

// Test on load
window.addEventListener('load', function() {
    console.log('Window loaded, testing API automatically...');
    setTimeout(testAPI, 1000);
});
</script>
@endsection