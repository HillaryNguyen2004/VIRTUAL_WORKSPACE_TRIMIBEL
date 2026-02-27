import { showToast } from "../show-toast";

$(function () {
    const $dialog = $("#update-user-dialog");
    const $form = $dialog.find("form");
    const $submit = $("#update-user-submit");
    const $spinner = $submit.find("[data-spinner]");
    let currentUserId = null;

    let initialMembersHTML = "";

    const getMembersWrapper = () => $dialog.find("#team-members-wrapper");
    const getTeamSection = () => $dialog.find("#team-select");

    const openDialog = () => {
        initialMembersHTML = getMembersWrapper().html() || "";
        $dialog.removeClass("hidden").addClass("flex");
        $("body").addClass("overflow-hidden");
    };

    const restoreMembers = () => {
        if (initialMembersHTML !== "") {
            getMembersWrapper().html(initialMembersHTML);
        }
    };

    const closeDialog = () => {
        restoreMembers();
        $form[0]?.reset();

        $dialog.removeClass("flex").addClass("hidden");
        $("body").removeClass("overflow-hidden");
    };

    // Show/hide the team select area based on role (staff -> show)
    const toggleTeamSelect = ($select) => {
        const show = $select.val() === "staff" || $select.val() === "substaff";
        const $section = getTeamSection();
        const $footerBtn = $dialog.find("#footer-btn");
        const $addMemberBtn = $dialog.find("#add-member-btn");

        if (!$section.length) return;
        if (show) {
            $section.removeClass("hidden").addClass("flex");
            $footerBtn.addClass("justify-between").removeClass("justify-end");
            $addMemberBtn.removeClass("hidden").addClass("flex");
        } else {
            $section.addClass("hidden").removeClass("flex");
            $footerBtn.addClass("justify-end").removeClass("justify-between");
            $addMemberBtn.addClass("hidden").removeClass("flex");
        }
    };

    // OPEN: the trigger is OUTSIDE the dialog => delegate on document
    $(document).on("click", ".open-update-user", function (e) {
        e.preventDefault();

        currentUserId = $(this).data("user-id");
        const name = $(this).data("user-name") ?? "";
        const role = ($(this).data("user-role") || "user").toLowerCase();

        // Update form action for the specific user
        if (currentUserId) {
            const updateUrl = `/users/${currentUserId}`;
            $form.attr("action", updateUrl);
        }

        // Fill fields
        $form.find('input[name="name"]').val(name);
        const $roleSelect = $form.find('select[name="role"]').val(role);

        toggleTeamSelect($roleSelect);

        // Filter role options based on leader status
        const hasLeader =
            $(this).data("has-leader") === true ||
            $(this).data("has-leader") === "true";
        const $staffOption = $roleSelect.find('option[value="staff"]');
        const $substaffOption = $roleSelect.find('option[value="substaff"]');

        if (hasLeader) {
            $staffOption.hide();
            $substaffOption.show();
        } else {
            $staffOption.show();
            $substaffOption.hide();
        }

        // Populate team members if leadership role
        const $wrapper = getMembersWrapper();
        $wrapper.empty(); // Fix: Clear previous data to prevent leakage

        if (role === "staff" || role === "substaff") {
            const members =
                (window.teamMembersByStaff &&
                    window.teamMembersByStaff[currentUserId]) ||
                [];
            if (members.length > 0) {
                members.forEach((member) => {
                    addTeamMemberField(member.id, true);
                });
            } else {
                addTeamMemberField(null, true); // add one empty row if none
            }
        }

        openDialog();
        updateAllSelects();
    });

    // Handle submit (listener added once)
    $submit.on("click", function (e) {
        e.preventDefault();

        const action = $form.attr("action");
        if (!action || action.includes("{{") || action.endsWith("/users/")) {
            showToast(
                "Error: Form destination not correctly set. Please re-open the dialog.",
                "danger",
            );
            return;
        }

        $form.trigger("submit");
    });

    $form.on("submit", function () {
        $submit.prop("disabled", true).addClass("opacity-50");
        $spinner.removeClass("hidden");
    });

    // close when clicking the close button
    $dialog.on("click", ".close-update-user", function (e) {
        e.preventDefault();
        closeDialog();
    });

    // close when clicking backdrop only
    $dialog.on("click", function (e) {
        if (e.target === this) closeDialog();
    });

    // Handle role change dynamically
    $(document).on("change", 'select[name="role"]', function () {
        const role = $(this).val();
        toggleTeamSelect($(this));

        if (role === "staff" || role === "substaff") {
            const $wrapper = getMembersWrapper();
            if ($wrapper.find(".team-member-select").length === 0) {
                addTeamMemberField(null, true);
            } else {
                updateAllSelects();
            }
        }
    });

    // Build <option> list from available users, excluding already selected ones
    function buildOptions(list, excludedIds = []) {
        const opts = [
            `<option value="">${
                (window.i18n && window.i18n.select_member) || "Select Member"
            }</option>`,
        ];
        (list || [])
            .filter((u) => !excludedIds.includes(String(u.id)))
            .forEach((u) => {
                const id = String(u.id).replace(/"/g, "&quot;");
                const name = String(u.name)
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;");
                opts.push(`<option value="${id}">${name}</option>`);
            });
        return opts.join("");
    }

    function updateAllSelects() {
        const userId = currentUserId;
        const $wrapper = $("#team-members-wrapper");
        if (!userId || !$wrapper.length) return;

        const available =
            window.availableUsers && window.availableUsers[userId]
                ? window.availableUsers[userId]
                : [];

        const allSelectedIds = [];
        const $selects = $wrapper.find('select[name="team_members[]"]');

        $selects.each(function () {
            const val = $(this).val();
            if (val) allSelectedIds.push(String(val));
        });

        $selects.each(function () {
            const $this = $(this);
            const currentVal = $this.val();

            // Exclude what others selected, but keep our own if selected
            const excludedForThis = allSelectedIds.filter(
                (id) => id !== String(currentVal),
            );

            const newOptions = buildOptions(available, excludedForThis);
            $this.html(newOptions);
            $this.val(currentVal);
        });
    }

    $(document).on("change", 'select[name="team_members[]"]', function () {
        updateAllSelects();
    });

    // Add a row
    window.addTeamMemberField = function (
        initialValue = null,
        suppressToast = false,
    ) {
        const userId = currentUserId;
        const $wrapper = $("#team-members-wrapper");
        if (!userId || !$wrapper.length) return;

        const available =
            window.availableUsers && window.availableUsers[userId]
                ? window.availableUsers[userId]
                : [];

        const $row = $(`
            <div class="team-member-select flex gap-2 w-full items-center">
                <div class="relative w-full">
                    <select name="team_members[]"
                        class="block w-full bg-canvas border border-muted-200 text-main cursor-pointer h-[50px] pl-4 pr-12 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none">
                        ${buildOptions(available, [])}
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                <button type="button"
                    class="remove-member-field-btn p-2.5 rounded-xl text-muted-400 hover:text-danger hover:bg-danger/10 transition-colors flex-shrink-0" 
                    title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `);

        if (initialValue) {
            const $select = $row.find('select[name="team_members[]"]');
            // Re-build options including this one explicitly if it was filtered out for some reason
            const opts = buildOptions(available, []);
            $select.html(opts);
            $select.val(initialValue);
        }

        $wrapper.append($row);
        updateAllSelects();
        if (!suppressToast) {
            showToast("Added a new member field", "success");
        }
    };

    // Remove a row
    $(document).on("click", ".remove-member-field-btn", function () {
        $(this).closest(".team-member-select").remove();
        updateAllSelects();
        showToast("Removed a member field", "success");
    });
});
