/**
 * Kare profile page behaviour.
 * Handles: avatar popover open/close, mobile sidebar toggle,
 * click-outside + Escape to close, scroll-entry animations,
 * and a small client-side "passwords match" hint.
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
  // State -> District cascading dropdown
  // ===================================================================

  var stateSelect = document.querySelector("[data-mr-state-select]");
  var districtSelect = document.querySelector("[data-mr-district-select]");

  if (stateSelect && districtSelect) {
    function loadDistricts(stateId, selectedDistrictId) {
      if (!stateId) {
        districtSelect.innerHTML =
          '<option value="">-- Select district --</option>';
        districtSelect.disabled = true;
        return;
      }

      districtSelect.disabled = true;
      districtSelect.innerHTML = '<option value="">Loading...</option>';

      fetch("get_districts.php?state_id=" + encodeURIComponent(stateId))
        .then(function (res) {
          return res.json();
        })
        .then(function (districts) {
          var html = '<option value="">-- Select district --</option>';
          districts.forEach(function (district) {
            var isSelected =
              selectedDistrictId &&
              String(selectedDistrictId) === String(district.id)
                ? " selected"
                : "";
            html +=
              '<option value="' +
              district.id +
              '"' +
              isSelected +
              ">" +
              district.name +
              "</option>";
          });
          districtSelect.innerHTML = html;
          districtSelect.disabled = false;
        })
        .catch(function () {
          districtSelect.innerHTML =
            '<option value="">-- Select district --</option>';
          districtSelect.disabled = false;
        });
    }

    stateSelect.addEventListener("change", function () {
      // A fresh state pick always starts the district over.
      loadDistricts(stateSelect.value, null);
    });
  }

  // ===================================================================
  // Change-password form: client-side "passwords match" hint
  // (server still re-validates everything — this is just UX polish)
  // ===================================================================

  var newPassword = document.getElementById("new_password");
  var confirmPassword = document.getElementById("confirm_password");

  if (newPassword && confirmPassword) {
    function checkPasswordsMatch() {
      if (confirmPassword.value === "") {
        confirmPassword.setCustomValidity("");
        return;
      }
      if (newPassword.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
      } else {
        confirmPassword.setCustomValidity("");
      }
    }

    newPassword.addEventListener("input", checkPasswordsMatch);
    confirmPassword.addEventListener("input", checkPasswordsMatch);
  }
});
