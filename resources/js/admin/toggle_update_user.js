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
            <div class="team-member-select flex gap-4 w-full">
                <select name="team_members[]"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                    ${buildOptions(available)}
                </select>
                <button id="remove-member-field-btn" type="button" class="px-3 rounded-full hover:bg-red-100 transition"
                    title="{{ __('tasks.delete') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-red-600">
                        <path
                            d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
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
