/**
 * MediRemind dashboard shell behaviour.
 * Handles: avatar popover open/close, mobile sidebar toggle,
 * click-outside + Escape to close, basic keyboard accessibility,
 * and IntersectionObserver-based scroll-entry animations.
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
  // Scroll-Entry Animations (IntersectionObserver)
  // ===================================================================
  // Elements with [data-mr-scroll-entry] start at opacity:0 + translateY(12px)
  // and resolve to their final state when entering the viewport.
  // Staggered delays are set via --index CSS variable on each element.

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
    // Fallback: if IntersectionObserver is not supported, show everything
    scrollEntries.forEach(function (el) {
      el.classList.add("is-visible");
    });
  }

  // ===================================================================
  // Table / Calendar view toggle
  // ===================================================================

  var viewButtons = document.querySelectorAll("[data-mr-view-btn]");
  viewButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var target = btn.dataset.mrViewBtn;

      viewButtons.forEach(function (b) {
        b.classList.toggle("is-active", b === btn);
      });

      document.querySelectorAll("[data-mr-view-panel]").forEach(function (panel) {
        panel.hidden = panel.dataset.mrViewPanel !== target;
      });
    });
  });

  // ===================================================================
  // Trend chart (README §6 "Not built" -> a real bar/line chart of dose
  // history). Data is computed server-side in reports.php and handed
  // off via the #mr-trend-data JSON script tag; Chart.js itself is
  // loaded via CDN <script> in reports.php's <head>, same no-build
  // pattern as Phosphor Icons.
  // ===================================================================

  var trendDataEl = document.getElementById("mr-trend-data");
  var trendCanvas = document.getElementById("mr-trend-chart");

  if (trendDataEl && trendCanvas && window.Chart) {
    var trend = JSON.parse(trendDataEl.textContent);

    new Chart(trendCanvas.getContext("2d"), {
      type: "bar",
      data: {
        labels: trend.labels,
        datasets: [
          {
            type: "bar",
            label: "Taken",
            data: trend.taken,
            backgroundColor: "#1f9d55",
            stack: "doses",
            borderRadius: 4,
            yAxisID: "yCount",
          },
          {
            type: "bar",
            label: "Missed",
            data: trend.missed,
            backgroundColor: "#e0433f",
            stack: "doses",
            borderRadius: 4,
            yAxisID: "yCount",
          },
          {
            type: "line",
            label: "Adherence rate",
            data: trend.rate,
            borderColor: "#55acee",
            backgroundColor: "#55acee",
            spanGaps: true,
            tension: 0.3,
            pointRadius: 2,
            yAxisID: "yRate",
          },
        ],
      },
      options: {
        responsive: true,
        interaction: { mode: "index", intersect: false },
        scales: {
          x: {
            stacked: true,
            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
            grid: { display: false },
          },
          yCount: {
            stacked: true,
            beginAtZero: true,
            position: "left",
            ticks: { precision: 0 },
            title: { display: true, text: "Doses" },
          },
          yRate: {
            beginAtZero: true,
            max: 100,
            position: "right",
            grid: { drawOnChartArea: false },
            title: { display: true, text: "Adherence %" },
          },
        },
        plugins: {
          legend: { position: "bottom" },
        },
      },
    });
  }
});
