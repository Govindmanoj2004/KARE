document.addEventListener("DOMContentLoaded", () => {
  const googleBtn = document.getElementById("google-btn");
  const facebookBtn = document.getElementById("facebook-btn");

  googleBtn.addEventListener("click", () => {
    console.log("Google login clicked");
  });

  facebookBtn.addEventListener("click", () => {
    console.log("Facebook login clicked");
  });

});
