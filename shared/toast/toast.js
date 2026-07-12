/**
 * Global Toast Component
 * Usage: showToast('error' | 'warning' | 'success', 'Your message here');
 * Requires: #toast-container in the DOM (rendered by shared/toast.php)
 *           Phosphor Icons loaded on the page
 */
function showToast(type, message) {
  var container = document.getElementById("toast-container");
  if (!container) return;

  var icons = {
    error: "ph-x-circle",
    warning: "ph-warning-circle",
    success: "ph-check-circle",
  };

  var toast = document.createElement("div");
  toast.className = "toast toast-" + type;
  toast.setAttribute("role", "alert");

  var icon = document.createElement("i");
  icon.className = "ph " + (icons[type] || icons.error);
  toast.appendChild(icon);

  var text = document.createElement("span");
  text.className = "toast-message";
  text.textContent = message;
  toast.appendChild(text);

  var closeBtn = document.createElement("button");
  closeBtn.className = "toast-close";
  closeBtn.setAttribute("aria-label", "Dismiss");
  closeBtn.innerHTML = '<i class="ph ph-x"></i>';
  closeBtn.addEventListener("click", function () {
    dismissToast(toast);
  });
  toast.appendChild(closeBtn);

  container.appendChild(toast);

  requestAnimationFrame(function () {
    toast.classList.add("is-visible");
  });

  var timerId = setTimeout(function () {
    dismissToast(toast);
  }, 4000);
  toast.dataset.timerId = timerId;
}

function dismissToast(toast) {
  clearTimeout(toast.dataset.timerId);
  toast.classList.remove("is-visible");
  toast.classList.add("is-leaving");
  toast.addEventListener("transitionend", function handler() {
    toast.removeEventListener("transitionend", handler);
    toast.remove();
  });
}

function initFlashToasts() {
  var container = document.getElementById("toast-container");
  if (!container || !container.dataset.flash) return;

  try {
    var data = JSON.parse(container.dataset.flash);
    var type = data.type || "error";
    var messages = data.messages || [];
    messages.forEach(function (msg, i) {
      setTimeout(function () {
        showToast(type, msg);
      }, i * 150);
    });
  } catch (e) {
    /* ignore malformed flash payload */
  }

  delete container.dataset.flash;
}

window.showToast = showToast;

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initFlashToasts);
} else {
  initFlashToasts();
}
