/**
 * Generic Confirm Modal
 * -----------------------------------------------------------------------
 * Wires up shared/modal/modal.php. Any button on the page with
 * [data-mr-confirm] opens the modal instead of acting immediately;
 * confirming submits the shared hidden form to that button's
 * data-mr-confirm-action / data-mr-confirm-method.
 * -----------------------------------------------------------------------
 */
document.addEventListener("DOMContentLoaded", function () {
  var overlay = document.querySelector("[data-mr-modal]");
  if (!overlay) return;

  var titleEl = overlay.querySelector("[data-mr-modal-title]");
  var messageEl = overlay.querySelector("[data-mr-modal-message]");
  var iconEl = overlay.querySelector("[data-mr-modal-icon] i");
  var cancelBtn = overlay.querySelector("[data-mr-modal-cancel]");
  var confirmBtn = overlay.querySelector("[data-mr-modal-confirm]");
  var form = document.querySelector("[data-mr-modal-form]");

  var lastTrigger = null;

  function openModal(trigger) {
    lastTrigger = trigger;

    var title = trigger.dataset.mrConfirmTitle || "Are you sure?";
    var message = trigger.dataset.mrConfirmMessage || "This action cannot be undone.";
    var confirmLabel = trigger.dataset.mrConfirmLabel || "Confirm";
    var icon = trigger.dataset.mrConfirmIcon || "ph-question";
    var variant = trigger.dataset.mrConfirmVariant || "default";
    var action = trigger.dataset.mrConfirmAction || "#";
    var method = trigger.dataset.mrConfirmMethod || "post";

    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmLabel;
    iconEl.className = "ph " + icon;

    if (variant === "danger") {
      overlay.setAttribute("data-mr-variant", "danger");
    } else {
      overlay.removeAttribute("data-mr-variant");
    }

    form.setAttribute("action", action);
    form.setAttribute("method", method);

    overlay.classList.add("is-open");
    overlay.setAttribute("aria-hidden", "false");
    confirmBtn.focus();
  }

  function closeModal() {
    overlay.classList.remove("is-open");
    overlay.setAttribute("aria-hidden", "true");
    if (lastTrigger) {
      lastTrigger.focus();
      lastTrigger = null;
    }
  }

  document.querySelectorAll("[data-mr-confirm]").forEach(function (trigger) {
    trigger.addEventListener("click", function (e) {
      e.preventDefault();
      openModal(trigger);
    });
  });

  cancelBtn.addEventListener("click", closeModal);

  confirmBtn.addEventListener("click", function () {
    form.submit();
  });

  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeModal();
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && overlay.classList.contains("is-open")) {
      closeModal();
    }
  });
});