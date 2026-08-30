/**
 * Kare doctors page behaviour.
 * Shell wiring (avatar popover, mobile sidebar, scroll-entry) same as
 * every other page, plus this page's own connect-request dialog — a
 * second, simpler overlay (not the shared confirm modal, which has no
 * room for a textarea) opened by any [data-mr-request-doctor] button.
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
      { threshold: 0.1, rootMargin: "0px 0px -40px 0px" },
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
  // Connect-request dialog
  // ===================================================================

  var overlay = document.querySelector("[data-mr-request-overlay]");
  if (!overlay) return;

  var doctorIdInput = overlay.querySelector("[data-mr-request-doctor-id]");
  var doctorNameEl = overlay.querySelector("[data-mr-request-doctor-name]");
  var cancelBtn = overlay.querySelector("[data-mr-request-cancel]");

  document.querySelectorAll("[data-mr-request-doctor]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      doctorIdInput.value = btn.dataset.doctorId;
      doctorNameEl.textContent = btn.dataset.doctorName;
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
