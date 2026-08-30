/**
 * Kare messages page behaviour.
 * Shell wiring (avatar popover, mobile sidebar, scroll-entry) same as
 * every other page, plus polling messages_poll.php every few seconds
 * for new messages in the open thread and appending them live.
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
  // Thread: scroll to bottom on load, poll for new messages
  // ===================================================================

  var threadEl = document.querySelector("[data-mr-thread-messages]");
  if (!threadEl) return;

  threadEl.scrollTop = threadEl.scrollHeight;

  var connectionId = threadEl.dataset.connectionId;
  var lastId = parseInt(threadEl.dataset.lastId, 10) || 0;

  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  function appendMessage(msg) {
    var wasNearBottom =
      threadEl.scrollHeight - threadEl.scrollTop - threadEl.clientHeight < 80;

    var bubble = document.createElement("div");
    bubble.className = "mr-bubble " + (msg.is_mine ? "is-mine" : "is-theirs");
    bubble.innerHTML =
      '<div class="mr-bubble-body">' +
      escapeHtml(msg.body).replace(/\n/g, "<br>") +
      '</div><div class="mr-bubble-time">' +
      escapeHtml(msg.created_at) +
      "</div>";
    threadEl.appendChild(bubble);

    if (wasNearBottom) {
      threadEl.scrollTop = threadEl.scrollHeight;
    }
  }

  function poll() {
    fetch("messages_poll.php?connection_id=" + encodeURIComponent(connectionId) + "&after_id=" + lastId)
      .then(function (res) {
        return res.ok ? res.json() : [];
      })
      .then(function (newMessages) {
        if (!Array.isArray(newMessages) || newMessages.length === 0) return;
        var emptyNotice = threadEl.querySelector(".mr-thread-empty");
        if (emptyNotice) emptyNotice.remove();
        newMessages.forEach(function (msg) {
          appendMessage(msg);
          lastId = Math.max(lastId, msg.id);
        });
      })
      .catch(function () {
        /* silently skip a failed poll — will retry on the next tick */
      });
  }

  var pollTimer = setInterval(poll, 3000);
  window.addEventListener("beforeunload", function () {
    clearInterval(pollTimer);
  });
});
