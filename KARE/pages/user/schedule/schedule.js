/**
 * Kare schedule page behaviour.
 * Handles: avatar popover, mobile sidebar toggle, scroll-entry animations
 * (same shell boilerplate as report.js), plus this page's own bits:
 *   - adding/removing reminder-time rows in the medicine form
 *   - "Edit" pre-filling the form to update an existing medicine
 */
document.addEventListener("DOMContentLoaded", function () {
  // ===================================================================
  // Avatar Popover
  // ===================================================================

  var avatarWrap = document.querySelector("[data-mr-avatar-wrap]");
  var avatarBtn = document.querySelector("[data-mr-avatar-btn]");

  if (avatarWrap && avatarBtn) {
    avatarBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      avatarWrap.classList.toggle("is-open");
      avatarBtn.setAttribute(
        "aria-expanded",
        avatarWrap.classList.contains("is-open") ? "true" : "false",
      );
    });

    document.addEventListener("click", function (e) {
      if (!avatarWrap.contains(e.target)) {
        avatarWrap.classList.remove("is-open");
        avatarBtn.setAttribute("aria-expanded", "false");
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        avatarWrap.classList.remove("is-open");
        avatarBtn.setAttribute("aria-expanded", "false");
      }
    });
  }

  // ===================================================================
  // Mobile Sidebar Toggle
  // ===================================================================

  var appShell = document.querySelector("[data-mr-app-shell]");
  var sidebarToggle = document.querySelector("[data-mr-sidebar-toggle]");

  if (appShell && sidebarToggle) {
    sidebarToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      appShell.classList.toggle("mr-sidebar-open");
    });

    document.addEventListener("click", function (e) {
      var sidebar = document.querySelector("[data-mr-sidebar]");
      if (
        appShell.classList.contains("mr-sidebar-open") &&
        sidebar &&
        !sidebar.contains(e.target) &&
        !sidebarToggle.contains(e.target)
      ) {
        appShell.classList.remove("mr-sidebar-open");
      }
    });
  }

  // ===================================================================
  // Scroll-Entry Animations
  // ===================================================================

  var scrollEntries = document.querySelectorAll("[data-mr-scroll-entry]");

  if (scrollEntries.length > 0 && "IntersectionObserver" in window) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.1,
        rootMargin: "0px 0px -40px 0px",
      },
    );

    scrollEntries.forEach(function (el) {
      observer.observe(el);
    });
  } else {
    scrollEntries.forEach(function (el) {
      el.classList.add("is-visible");
    });
  }

  // ===================================================================
  // Reminder-time rows: add / remove
  // ===================================================================

  var timeList = document.querySelector("[data-mr-time-list]");
  var addTimeBtn = document.querySelector("[data-mr-add-time]");

  function makeTimeRow(value) {
    var row = document.createElement("div");
    row.className = "mr-time-row";
    row.innerHTML =
      '<input class="mr-input" type="time" name="times[]" required>' +
      '<button type="button" class="mr-icon-btn" data-mr-remove-time title="Remove time"><i class="ph ph-x"></i></button>';
    if (value) {
      row.querySelector("input").value = value;
    }
    return row;
  }

  if (timeList && addTimeBtn) {
    addTimeBtn.addEventListener("click", function () {
      timeList.appendChild(makeTimeRow());
    });

    timeList.addEventListener("click", function (e) {
      var removeBtn = e.target.closest("[data-mr-remove-time]");
      if (!removeBtn) return;
      // Always keep at least one time row.
      if (timeList.querySelectorAll(".mr-time-row").length > 1) {
        removeBtn.closest(".mr-time-row").remove();
      } else {
        removeBtn.closest(".mr-time-row").querySelector("input").value = "";
      }
    });
  }

  // ===================================================================
  // Edit medicine: pre-fill the add form and switch it to "update" mode
  // ===================================================================

  var form = document.querySelector("[data-mr-medicine-form]");
  var formHeading = document.querySelector("[data-mr-form-heading]");
  var formAction = document.querySelector("[data-mr-form-action]");
  var formMedicineId = document.querySelector("[data-mr-form-medicine-id]");
  var formName = document.querySelector("[data-mr-form-name]");
  var formDosage = document.querySelector("[data-mr-form-dosage]");
  var formNotes = document.querySelector("[data-mr-form-notes]");
  var formSubmit = document.querySelector("[data-mr-form-submit]");
  var formCancel = document.querySelector("[data-mr-form-cancel]");

  function resetForm() {
    if (!form) return;
    form.reset();
    formAction.value = "create_medicine";
    formMedicineId.value = "";
    formHeading.textContent = "Add a medicine";
    formSubmit.innerHTML = '<i class="ph ph-plus"></i> Add medicine';
    formCancel.style.display = "none";
    timeList.innerHTML = "";
    timeList.appendChild(makeTimeRow());
  }

  document.querySelectorAll("[data-mr-edit-medicine]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (!form) return;

      formAction.value = "update_medicine";
      formMedicineId.value = btn.dataset.medicineId;
      formName.value = btn.dataset.name || "";
      formDosage.value = btn.dataset.dosage || "";
      formNotes.value = btn.dataset.notes || "";
      formHeading.textContent = "Edit medicine";
      formSubmit.innerHTML = '<i class="ph ph-check"></i> Save changes';
      formCancel.style.display = "";

      var times = (btn.dataset.times || "").split(",").filter(Boolean);
      timeList.innerHTML = "";
      if (times.length === 0) {
        timeList.appendChild(makeTimeRow());
      } else {
        times.forEach(function (t) {
          timeList.appendChild(makeTimeRow(t));
        });
      }

      form.scrollIntoView({ behavior: "smooth", block: "center" });
    });
  });

  if (formCancel) {
    formCancel.addEventListener("click", resetForm);
  }
});
