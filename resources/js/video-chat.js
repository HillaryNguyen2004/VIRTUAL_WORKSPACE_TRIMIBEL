let selectedSlot = null;
let smartSelectedAttendees = []; 

function toggleScheduleModal(show) {
    const modal = document.getElementById('scheduleMeetingModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
        // Clear inputs on close
        document.getElementById('schTitle').value = '';
        document.getElementById('schDate').value = '';
        document.getElementById('schStart').value = '';
        document.getElementById('schEnd').value = '';
    }
}

// Attach this to your main "Schedule Meeting" button on the page
// document.getElementById('YOUR_TRIGGER_BUTTON_ID').addEventListener('click', () => toggleScheduleModal(true));

document.getElementById('btnSaveMeeting').addEventListener('click', async function() {
    const title = document.getElementById('schTitle').value;
    const date = document.getElementById('schDate').value;
    const start = document.getElementById('schStart').value;
    const end = document.getElementById('schEnd').value;

    if(!title || !date || !start || !end) {
        alert('Please fill in all details.');
        return;
    }

    // Format dates for the backend (YYYY-MM-DD HH:MM:SS)
    const startDate = `${date} ${start}:00`;
    const endDate = `${date} ${end}:00`;

    const btn = this;
    const spinner = document.getElementById('schSpinner');
    
    // UI Loading State
    btn.disabled = true;
    spinner.classList.remove('hidden');

    try {
        // 1. Generate Room via Metered
        let roomResponse = await fetch(document.querySelector('[data-meetings-generate]')?.getAttribute('data-meetings-generate') || '/api/meetings/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });
        
        let roomData = await roomResponse.json();
        if(!roomData.success) throw new Error(roomData.message || 'Failed to generate meeting link.');

        const meetingId = roomData.roomName;

        // 2. Save it to the Calendar Event Database
        let calResponse = await fetch(document.querySelector('[data-calendar-store]')?.getAttribute('data-calendar-store') || '/calendar/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                title: title,
                start_date: startDate,
                end_date: endDate,
                category: 'meeting',
                meeting_id: meetingId, 
                recurrence_type: 'none' // Default
            })
        });

        let calData = await calResponse.json();
        
        if(calData.status === 'success') {
            alert(`Meeting scheduled successfully! Your Meeting ID is: ${meetingId}`);
            toggleScheduleModal(false);
            
            // Optional: Refresh the page so the new meeting appears in their meeting history list
            window.location.reload(); 
        } else {
            throw new Error(calData.message || 'Failed to save to calendar.');
        }

    } catch (error) {
        console.error('Scheduling Error:', error);
        alert('Error: ' + error.message);
    } finally {
        // Restore UI State
        btn.disabled = false;
        spinner.classList.add('hidden');
    }
});

function toggleSmartMeetingModal(show) {
    const modal = document.getElementById('smartMeetingModal');
    if (show) {
        modal.classList.remove('hidden');
        // Reset UI
        document.getElementById('smartStep1').classList.remove('hidden');
        document.getElementById('smartStep2').classList.add('hidden');
        document.getElementById('btnFindSlots').classList.remove('hidden');
        document.getElementById('btnBookSmartMeeting').classList.add('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

// 1. Fetch Available Slots
document.getElementById('btnFindSlots').addEventListener('click', async function() {
    const attendees = smartSelectedAttendees.map(user => user.id);
    const duration = parseInt(document.getElementById('smartDuration').value, 10);
    const title = document.getElementById('smartTitle').value;

    if(!title || attendees.length === 0) {
        alert('Please provide a title and select at least one attendee.');
        return;
    }

    if(isNaN(duration) || duration < 5) {
        alert('Please enter a valid meeting duration (minimum 5 minutes).');
        return;
    }

    const btn = this;
    btn.innerHTML = 'Analyzing Calendars...';
    btn.disabled = true;

    try {
        let response = await fetch(document.querySelector('[data-meetings-smart-slots]')?.getAttribute('data-meetings-smart-slots') || '/meetings/smart/slots', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ attendees, duration })
        });
        
        let data = await response.json();
        
        if(data.status === 'success') {
            const container = document.getElementById('slotContainer');
            container.innerHTML = '';
            
            if(data.slots.length === 0) {
                container.innerHTML = '<p class="text-sm text-muted-500">No common slots found in the next 7 days.</p>';
            } else {
                data.slots.forEach((slot, index) => {
                    container.innerHTML += `
                        <label class="flex items-center gap-3 p-3 border border-muted-200 rounded-xl cursor-pointer hover:border-primary hover:bg-primary/5 transition-all">
                            <input type="radio" name="smartSlot" value='${JSON.stringify(slot)}' class="text-primary focus:ring-primary h-4 w-4" onchange="selectedSlot = this.value">
                            <span class="text-sm font-medium text-main">${slot.display}</span>
                        </label>
                    `;
                });
            }

            // Switch UI to Step 2
            document.getElementById('smartStep1').classList.add('hidden');
            document.getElementById('smartStep2').classList.remove('hidden');
            btn.classList.add('hidden');
            document.getElementById('btnBookSmartMeeting').classList.remove('hidden');
            document.getElementById('btnBookSmartMeeting').classList.add('flex');
        }
    } catch (e) {
        alert('Error finding slots.');
    } finally {
        btn.innerHTML = 'Find Time Slots';
        btn.disabled = false;
    }
});

// 2. Book the Meeting
document.getElementById('btnBookSmartMeeting').addEventListener('click', async function() {
    const selectedRadio = document.querySelector('input[name="smartSlot"]:checked');
    
    if(!selectedRadio) return alert('Please select a time slot.');

    const slotData = JSON.parse(selectedRadio.value);
    const attendees = smartSelectedAttendees.map(user => user.id);        
    const title = document.getElementById('smartTitle').value;
    const btn = this;

    btn.innerHTML = 'Booking...';
    btn.disabled = true;

    try {
        // A. Generate Metered Room First (reusing your existing logic)
        let roomResponse = await fetch(document.querySelector('[data-meetings-generate]')?.getAttribute('data-meetings-generate') || '/api/meetings/generate', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });
        let roomData = await roomResponse.json();
        if(!roomData.success) throw new Error('Failed to generate video link');

        // B. Save to everyone's calendar
        let bookResponse = await fetch(document.querySelector('[data-meetings-smart-book]')?.getAttribute('data-meetings-smart-book') || '/meetings/smart/book', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                title: title,
                start_date: slotData.start,
                end_date: slotData.end,
                attendees: attendees,
                meeting_id: roomData.roomName
            })
        });

        // Get response text first to debug
        const responseText = await bookResponse.text();
        console.log('Book response status:', bookResponse.status);
        console.log('Book response text:', responseText);

        if (!bookResponse.ok) {
            try {
                const errData = JSON.parse(responseText);
                throw new Error(errData.message || `Server error: ${bookResponse.status}`);
            } catch (parseErr) {
                throw new Error(`Server rejected the booking (${bookResponse.status}): ${responseText}`);
            }
        }

        let bookData = JSON.parse(responseText);
        if(bookData.status === 'success') {
            alert('Meeting booked successfully and added to all calendars!');
            window.location.reload();
        } else {
            throw new Error(bookData.message || 'Booking failed - unexpected response');
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    } finally {
        btn.innerHTML = 'Book Selected Slot';
        btn.disabled = false;
    }
});

// 1. UI Event Listeners for the Search & Select
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('smartUserSearch');
    const noResultsEl = document.getElementById('smartUserNoResults');
    const options = document.querySelectorAll('.smart-user-option');

    // Filter logic as user types
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        let hasVisible = false;

        options.forEach(option => {
            const searchString = option.getAttribute('data-search');
            const id = option.getAttribute('data-id');
            
            // Hide if it doesn't match search OR if it's already selected
            if (searchString.includes(searchTerm) && !smartSelectedAttendees.some(user => user.id === id)) {
                option.classList.remove('hidden');
                option.classList.add('flex');
                hasVisible = true;
            } else {
                option.classList.add('hidden');
                option.classList.remove('flex');
            }
        });

        // Show/Hide "No Results" message
        if (!hasVisible) {
            noResultsEl.classList.remove('hidden');
        } else {
            noResultsEl.classList.add('hidden');
        }
    });

    // Handle clicking a user from the list
    options.forEach(option => {
        option.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            // Add to state array
            smartSelectedAttendees.push({ id, name });
            
            // Re-render badges
            renderSmartBadges();

            // Hide the clicked option, clear search, and refocus
            this.classList.add('hidden');
            this.classList.remove('flex');
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input')); // Trigger filter reset
            searchInput.focus(); 
        });
    });
});

// Render the selected user badges
function renderSmartBadges() {
    const container = document.getElementById('smartSelectedUsers');
    container.innerHTML = ''; // Clear current

    smartSelectedAttendees.forEach(user => {
        const badge = document.createElement('div');
        badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-sm font-medium border border-primary/20 animate-fade-in-up';
        badge.innerHTML = `
            ${user.name}
            <button type="button" class="hover:text-primary-hover focus:outline-none" onclick="removeSmartUser('${user.id}')">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;
        container.appendChild(badge);
    });
}

// Remove user from selection
window.removeSmartUser = function(id) {
    // Remove from state array
    smartSelectedAttendees = smartSelectedAttendees.filter(user => user.id !== id);
    
    // Re-render badges
    renderSmartBadges();

    // Show the option back in the dropdown list
    const option = document.querySelector(`.smart-user-option[data-id="${id}"]`);
    if (option) {
        // Only show it if it matches current search
        const searchTerm = document.getElementById('smartUserSearch').value.toLowerCase();
        if (option.getAttribute('data-search').includes(searchTerm)) {
            option.classList.remove('hidden');
            option.classList.add('flex');
        }
    }
};

// Also, reset the array when the modal closes so it's clean for the next time
const originalToggleModal = window.toggleSmartMeetingModal;
window.toggleSmartMeetingModal = function(show) {
    if(!show) {
        smartSelectedAttendees = [];
        renderSmartBadges();
        document.getElementById('smartUserSearch').value = '';
        // Reset all options to be visible
        document.querySelectorAll('.smart-user-option').forEach(opt => {
            opt.classList.remove('hidden');
            opt.classList.add('flex');
        });
    }
    // Call the original modal toggle logic from your previous setup
    originalToggleModal(show); 
};

// Attach listeners
document.getElementById('btnOpenScheduleModal').addEventListener('click', () => {
    toggleScheduleModal(true);
});

document.getElementById('btnOpenSmartModal').addEventListener('click', () => {
    toggleSmartMeetingModal(true);
});

document.getElementById('btnCloseScheduleModal').addEventListener('click', () => {
    toggleScheduleModal(false);
});

document.getElementById('btnCloseSmartModal').addEventListener('click', () => {
    toggleSmartMeetingModal(false);
});
