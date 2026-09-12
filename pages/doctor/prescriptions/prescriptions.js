/**
 * Kare doctor prescriptions page behaviour.
 * Handles: avatar popover, mobile sidebar toggle, scroll-entry animations
 * (same shell boilerplate as every other doctor page), plus this page's
 * own bit: the fulfill-request dialog (upload a new prescription for a
 * specific pending request), same overlay pattern as
 * pages/user/doctors/doctors.js's connect-request dialog.
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
  // Fulfill-request dialog
  // ===================================================================

  var overlay = document.querySelector("[data-mr-fulfill-overlay]");
  if (!overlay) return;

  var requestIdInput = overlay.querySelector("[data-mr-fulfill-request-id]");
  var patientNameEl = overlay.querySelector("[data-mr-fulfill-patient-name]");
  var cancelBtn = overlay.querySelector("[data-mr-fulfill-cancel]");

  document.querySelectorAll("[data-mr-fulfill-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      requestIdInput.value = btn.dataset.requestId;
      patientNameEl.textContent = btn.dataset.patientName;
      overlay.classList.add("is-open");
      overlay.setAttribute("aria-hidden", "false");
    });
  });

  function closeOverlay() {
    overlay.classList.remove("is-open");
    overlay.setAttribute("aria-hidden", "true");
  }

  cancelBtn.addEventListener("click", closeOverlay);
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeOverlay();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && overlay.classList.contains("is-open")) closeOverlay();
  });
});
