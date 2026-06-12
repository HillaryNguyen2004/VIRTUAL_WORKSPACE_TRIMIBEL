import $ from "jquery";
window.$ = window.jQuery = $;

(function ($) {
  "use strict";

  function buildOptions($assignee, leaderId, selectedId) {
    const $placeholder = $assignee.find('option[value=""]').first();
    const placeholderHtml = $placeholder.length
      ? $placeholder.prop("outerHTML")
      : '<option value="">Select assignee</option>';

    $assignee.empty().append(placeholderHtml);

    const list = (window.assigneesByLeader || {})[leaderId] || [];
    list.forEach(function (u) {
      const $opt = $("<option>", { value: u.id, text: u.name });
      if (selectedId && String(u.id) === String(selectedId)) {
        $opt.prop("selected", true);
      }
      $assignee.append($opt);
    });
  }

  // -------- CREATE (multi tasks) --------
  function populateAssigneesCreate($taskBlock, projectId) {
    const $assignee = $taskBlock
      .find('select[name^="tasks["][name$="[assignee]"]')
      .first();

    if ($assignee.length === 0) return;

    const leaderId = (window.projectLeaderMap || {})[projectId];
    if (!leaderId) {
      buildOptions($assignee, null);
      return;
    }

    buildOptions($assignee, leaderId, null);
  }

  // -------- EDIT (single task) --------
  function populateAssigneesEdit(projectId) {
    const $assignee = $('select[name="assignee"]').first();
    if ($assignee.length === 0) return;

    const leaderId = (window.projectLeaderMap || {})[projectId];
    if (!leaderId) {
      buildOptions($assignee, null);
      return;
    }

    buildOptions($assignee, leaderId, window.currentEditAssigneeId);
  }

  $(function () {
    // CREATE: change project in any task block
    $("#tasks-container").on(
      "change",
      'select[name^="tasks["][name$="[project_id]"]',
      function () {
        const $block = $(this).closest(".task-block");
        populateAssigneesCreate($block, $(this).val());
      }
    );

    // EDIT: change project_id
    $(document).on("change", 'select[name="project_id"]', function () {
      populateAssigneesEdit($(this).val());
    });

    // CREATE: initial population
    $(".task-block").each(function () {
      const $block = $(this);
      const projectId = $block
        .find('select[name^="tasks["][name$="[project_id]"]')
        .val();

      if (projectId) populateAssigneesCreate($block, projectId);
    });

    // EDIT: initial population
    const $editProject = $('select[name="project_id"]').first();
    if ($editProject.length) {
      const pid = $editProject.val() || window.currentEditProjectId;
      if (pid) populateAssigneesEdit(pid);
    }
  });
})(jQuery);
