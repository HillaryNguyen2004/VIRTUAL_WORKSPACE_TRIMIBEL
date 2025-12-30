import { showToast } from "../show-toast";

$(function () {
    const $dialog = $("#update-user-dialog");
    const $form = $dialog.find("form");
    const $submit = $("#update-user-submit");
    const $spinner = $submit.find("[data-spinner]");

    let initialMembersHTML = "";

    const getMembersWrapper = () =>
        $dialog.find('[id^="team-members-wrapper-"]');
    const getTeamSection = () => $dialog.find('[id^="team-select-"]');

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
        const show = $select.val() === "staff";
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

        // Optional: prefill from data-*
        const id = $(this).data("user-id");
        const name = $(this).data("user-name") ?? "";
        const role = ($(this).data("user-role") || "user").toLowerCase();

        $submit.on("click", function (e) {
            e.preventDefault();

            $submit.prop("disabled", true).addClass("opacity-50");

            $form.trigger("submit");

            setTimeout(() => {
                $submit.prop("disabled", false).removeClass("opacity-50");
            }, 2000);
        });

        // show a loading indicator
        $form.on("submit", function () {
            $submit.prop("disabled", true).addClass("opacity-50");
            $spinner.removeClass("hidden");
        });

        if ($form.find('input[name="_method"]').length === 0) {
            $form.append('<input type="hidden" name="_method" value="PUT">');
        }

        // Fill fields if they exist
        $form.find('input[name="name"]').val(name);
        const $roleSelect = $form.find('select[name="role"]').val(role);

        toggleTeamSelect($roleSelect);

        openDialog();
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

    // Build <option> list from available users
    function buildOptions(list) {
        const opts = [
            `<option value="">${
                window.i18n && window.i18n.select_member
            }</option>`,
        ];
        (list || []).forEach((u) => {
            // Escape basic chars
            const id = String(u.id).replace(/"/g, "&quot;");
            const name = String(u.name)
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
            opts.push(`<option value="${id}">${name}</option>`);
        });
        return opts.join("");
    }

    // Add a row
    window.addTeamMemberField = function (userId) {
        const $wrapper = $(`#team-members-wrapper-${userId}`);
        if (!$wrapper.length) return;

        const available =
            window.availableUsers && window.availableUsers[userId]
                ? window.availableUsers[userId]
                : [];

        const $row = $(`
            <div class="team-member-select flex gap-2 w-full items-center">
                <div class="relative w-full">
                    <select name="team_members[]"
                        class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        ${buildOptions(available)}
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
                <button id="remove-member-field-btn" type="button"
                    class="p-2.5 rounded-xl text-muted-400 hover:text-danger hover:bg-danger/10 transition-colors flex-shrink-0" 
                    title="{{ __('tasks.delete') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `);

        $wrapper.append($row);
        showToast("Added a new member field", "success");
    };

    // Remove a row
    $(document).on("click", "#remove-member-field-btn", function () {
        $(this).closest(".team-member-select").remove();
        showToast("Removed a member field", "success");
    });
});
