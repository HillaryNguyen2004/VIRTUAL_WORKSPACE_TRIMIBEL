function toggleEditForm(id) {
    const editForm = document.getElementById(`edit-form-${id}`);
    const viewTeam = document.getElementById(`view-team-${id}`);
    editForm.classList.toggle('d-none');
    if (!editForm.classList.contains('d-none')) {
        viewTeam.classList.add('d-none');
    }
}

function toggleViewTeam(id) {
    const viewTeam = document.getElementById(`view-team-${id}`);
    const editForm = document.getElementById(`edit-form-${id}`);
    viewTeam.classList.toggle('d-none');
    if (!viewTeam.classList.contains('d-none')) {
        editForm.classList.add('d-none');
    }
}

function toggleTeamSelect(select, userId) {
    const div = document.getElementById(`team-select-${userId}`);
    div.classList.toggle('d-none', select.value !== 'staff');
}

// function addTeamMemberField(userId) {
//     const wrapper = document.getElementById(`team-members-wrapper-${userId}`);
//     const container = document.createElement('div');
//     container.className = 'd-flex mb-2 align-items-center team-member-select';

//     const select = document.createElement('select');
//     select.name = 'team_members[]';
//     select.className = 'form-select me-2';
//     select.innerHTML = `<option value="">-- Select Member --</option>
//         ${Array.from(document.querySelectorAll(`#team-members-wrapper-${userId} select[name="team_members[]"]`))
//             .map(s => `<option value="${s.value}">${s.options[s.selectedIndex].text}</option>`)
//             .join('')}`;

//     const removeBtn = document.createElement('button');
//     removeBtn.type = 'button';
//     removeBtn.className = 'btn btn-outline-danger btn-sm';
//     removeBtn.innerText = '🗑';
//     removeBtn.onclick = () => container.remove();

//     container.appendChild(select);
//     container.appendChild(removeBtn);
//     wrapper.appendChild(container);
// }

function addTeamMemberField(userId) {
    const wrapper = document.getElementById(`team-members-wrapper-${userId}`);
    const container = document.createElement('div');
    container.className = 'd-flex mb-2 align-items-center team-member-select';

    // Get available users for this staff
    const available = (window.availableUsers && window.availableUsers[userId]) ? window.availableUsers[userId] : [];

    const select = document.createElement('select');
    select.name = 'team_members[]';
    select.className = 'form-select me-2';
    select.innerHTML = `<option value="">-- Select Member --</option>` +
        available.map(u => `<option value="${u.id}">${u.name}</option>`).join('');

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-outline-danger btn-sm';
    removeBtn.innerText = '🗑';
    removeBtn.onclick = () => container.remove();

    container.appendChild(select);
    container.appendChild(removeBtn);
    wrapper.appendChild(container);
}

function removeTeamMemberField(button) {
    button.closest('.team-member-select').remove();
}
