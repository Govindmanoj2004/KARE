document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("signup-9-form");
  const googleBtn = document.getElementById("google-btn");
  const facebookBtn = document.getElementById("facebook-btn");
  const termsCheckbox = document.getElementById("terms");

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    if (!termsCheckbox.checked) {
      termsCheckbox.focus();
      return;
    }

    const username = document.getElementById("username").value;
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    console.log("Signup submitted:", { username, email, password });
  });

  googleBtn.addEventListener("click", () => {
    console.log("Google signup clicked");
  });

  facebookBtn.addEventListener("click", () => {
    console.log("Facebook signup clicked");
  });
});
