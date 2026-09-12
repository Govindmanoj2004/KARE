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
  // Redesigned (to-do #11): the confusing native <input type="time"> —
  // whose tiny HH/MM/AM-PM segments are fiddly to click, especially on
  // mobile — is replaced with three plain <select> dropdowns (hour,
  // 5-minute steps, AM/PM). A hidden input still carries the same
  // "HH:MM" 24-hour value the controller already expects, so no backend
  // change is needed.

  var timeList = document.querySelector("[data-mr-time-list]");
  var addTimeBtn = document.querySelector("[data-mr-add-time]");

  function pad2(n) {
    return n < 10 ? "0" + n : "" + n;
  }

  // "08:00" (24h, as stored/submitted) -> { hour12: 8, minute: "00", ampm: "AM" }
  function timeToParts(value) {
    var bits = (value || "08:00").split(":");
    var h24 = parseInt(bits[0], 10);
    if (isNaN(h24)) h24 = 8;
    var minNum = parseInt(bits[1], 10);
    if (isNaN(minNum)) minNum = 0;
    minNum = Math.round(minNum / 5) * 5; // snap to the nearest 5-minute option
    if (minNum === 60) minNum = 0;
    var ampm = h24 >= 12 ? "PM" : "AM";
    var h12 = h24 % 12;
    if (h12 === 0) h12 = 12;
    return { hour12: h12, minute: pad2(minNum), ampm: ampm };
  }

  // (8, "00", "PM") -> "20:00"
  function partsTo24h(hour12, minute, ampm) {
    var h = parseInt(hour12, 10) % 12;
    if (ampm === "PM") h += 12;
    return pad2(h) + ":" + minute;
  }

  function makeTimeRow(value) {
    var parts = timeToParts(value);
    var row = document.createElement("div");
    row.className = "mr-time-row";

    var hourOptions = "";
    for (var h = 1; h <= 12; h++) {
      hourOptions += '<option value="' + h + '"' + (h === parts.hour12 ? " selected" : "") + ">" + h + "</option>";
    }
    var minuteOptions = "";
    for (var m = 0; m < 60; m += 5) {
      var mm = pad2(m);
      minuteOptions += '<option value="' + mm + '"' + (mm === parts.minute ? " selected" : "") + ">" + mm + "</option>";
    }
    var ampmOptions =
      '<option value="AM"' + (parts.ampm === "AM" ? " selected" : "") + ">AM</option>" +
      '<option value="PM"' + (parts.ampm === "PM" ? " selected" : "") + ">PM</option>";

    row.innerHTML =
      '<div class="mr-time-select-group">' +
      '<select class="mr-input mr-time-select" data-mr-time-hour aria-label="Hour">' + hourOptions + "</select>" +
      '<span class="mr-time-colon">:</span>' +
      '<select class="mr-input mr-time-select" data-mr-time-minute aria-label="Minute">' + minuteOptions + "</select>" +
      '<select class="mr-input mr-time-select mr-time-ampm" data-mr-time-ampm aria-label="AM or PM">' + ampmOptions + "</select>" +
      "</div>" +
      '<input type="hidden" name="times[]" data-mr-time-value>' +
      '<button type="button" class="mr-icon-btn" data-mr-remove-time title="Remove time"><i class="ph ph-x"></i></button>';

    var hourSel = row.querySelector("[data-mr-time-hour]");
    var minSel = row.querySelector("[data-mr-time-minute]");
    var ampmSel = row.querySelector("[data-mr-time-ampm]");
    var hidden = row.querySelector("[data-mr-time-value]");

    function sync() {
      hidden.value = partsTo24h(hourSel.value, minSel.value, ampmSel.value);
    }
    hourSel.addEventListener("change", sync);
    minSel.addEventListener("change", sync);
    ampmSel.addEventListener("change", sync);
    sync();

    return row;
  }

  // The page loads with an empty time-list container (see schedule.php) —
  // give it its first row here so there's a single source of truth for
  // this markup instead of duplicating it in PHP too.
  if (timeList && timeList.children.length === 0) {
    timeList.appendChild(makeTimeRow());
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
        timeList.innerHTML = "";
        timeList.appendChild(makeTimeRow());
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
