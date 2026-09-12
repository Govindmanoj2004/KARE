document.addEventListener("DOMContentLoaded", () => {
  const googleBtn = document.getElementById("google-btn");
  const facebookBtn = document.getElementById("facebook-btn");

  // googleBtn.addEventListener("click", () => {
  //   console.log("Google signup clicked");
  // });

  // facebookBtn.addEventListener("click", () => {
  //   console.log("Facebook signup clicked");
  // });

  // ===================================================================
  // Patient / Doctor role toggle (to-do #4: doctor signup)
  // ===================================================================
  const roleInput = document.getElementById("role");
  const roleButtons = document.querySelectorAll("[data-mr-role-btn]");
  const specialtyField = document.getElementById("specialty");

  function applyRole(role) {
    roleButtons.forEach((btn) => {
      const isActive = btn.dataset.mrRoleBtn === role;
      btn.classList.toggle("is-active", isActive);
      btn.setAttribute("aria-selected", isActive ? "true" : "false");
    });
    if (roleInput) roleInput.value = role;
    if (specialtyField) {
      const isDoctor = role === "doctor";
      specialtyField.style.display = isDoctor ? "" : "none";
      specialtyField.required = isDoctor;
      if (!isDoctor) specialtyField.value = specialtyField.value; // keep any typed text if they switch back
    }
  }

  roleButtons.forEach((btn) => {
    btn.addEventListener("click", () => applyRole(btn.dataset.mrRoleBtn));
  });

  // Restore the selected role after a failed submit (controller repopulates
  // the hidden #role input's value via old()).
  applyRole(roleInput && roleInput.value === "doctor" ? "doctor" : "patient");
});
