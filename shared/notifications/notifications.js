/**
 * shared/notifications/notifications.js
 * -----------------------------------------------------------------------
 * Wires up the navbar bell dropdown: [data-mr-notif-wrap] (see navbar.php
 * in shared/user, shared/doctor, shared/admin -- markup is identical in
 * all three). Loads on page load, then polls every 20s (same style as
 * messages.js's 3s poll, just far less latency-sensitive so a longer
 * interval is enough). No build step / no framework, same as the rest
 * of the project.
 * -----------------------------------------------------------------------
 */
document.addEventListener("DOMContentLoaded", function () {
  var wrap = document.querySelector("[data-mr-notif-wrap]");
  if (!wrap) return;

  var btn = wrap.querySelector("[data-mr-notif-btn]");
  var dot = wrap.querySelector("[data-mr-notif-dot]");
  var list = wrap.querySelector("[data-mr-notif-list]");
  var markAllBtn = wrap.querySelector("[data-mr-notif-mark-all]");
  var fetchUrl = wrap.getAttribute("data-mr-notif-fetch");
  var markUrl = wrap.getAttribute("data-mr-notif-mark");
  var rootBase = wrap.getAttribute("data-mr-root-base") || "";

  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  function render(data) {
    var notifications = data.notifications || [];
    var unread = data.unread_count || 0;

    if (dot) dot.hidden = unread === 0;
    if (markAllBtn) markAllBtn.disabled = unread === 0;

    if (notifications.length === 0) {
      list.innerHTML = '<div class="mr-notif-empty">You\'re all caught up.</div>';
      return;
    }

    var html = "";
    notifications.forEach(function (n) {
      html +=
        '<button type="button" class="mr-notif-item' +
        (n.is_read ? " is-read" : "") +
        '" data-mr-notif-id="' +
        n.id +
        '"' +
        (n.link ? ' data-mr-notif-link="' + escapeHtml(rootBase + n.link) + '"' : "") +
        '>' +
        '<span class="mr-notif-unread-dot"></span>' +
        '<span class="mr-notif-item-body">' +
        '<span class="mr-notif-item-text">' + escapeHtml(n.body) + "</span>" +
        '<span class="mr-notif-item-time">' + escapeHtml(n.created_at) + "</span>" +
        "</span>" +
        "</button>";
    });
    list.innerHTML = html;
  }

  function load() {
    if (!fetchUrl) return;
    fetch(fetchUrl, { credentials: "same-origin" })
      .then(function (res) {
        return res.json();
      })
      .then(render)
      .catch(function () {
        /* Silently ignore -- the bell just won't update this cycle. */
      });
  }

  if (btn && wrap) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      var isOpen = wrap.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
      if (isOpen) load();
    });

    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) {
        wrap.classList.remove("is-open");
        btn.setAttribute("aria-expanded", "false");
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        wrap.classList.remove("is-open");
        btn.setAttribute("aria-expanded", "false");
      }
    });
  }

  if (list) {
    list.addEventListener("click", function (e) {
      var item = e.target.closest("[data-mr-notif-id]");
      if (!item) return;
      var id = item.getAttribute("data-mr-notif-id");
      var link = item.getAttribute("data-mr-notif-link");

      if (markUrl) {
        var body = new URLSearchParams();
        body.set("action", "mark_read");
        body.set("id", id);
        fetch(markUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: body.toString(),
        }).finally(function () {
          if (link) window.location.href = link;
        });
      } else if (link) {
        window.location.href = link;
      }
    });
  }

  if (markAllBtn && markUrl) {
    markAllBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      var body = new URLSearchParams();
      body.set("action", "mark_all_read");
      fetch(markUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body.toString(),
      }).then(load);
    });
  }

  // Initial load (so the dot is correct even before the dropdown is opened),
  // then poll periodically for new notifications.
  load();
  setInterval(load, 20000);
});
