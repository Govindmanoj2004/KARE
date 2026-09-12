/**
 * guest.js
 * -----------------------------------------------------------------------
 * Landing page behaviour: just the fade-in-on-scroll for [data-mr-fade]
 * elements (a lighter copy of base.css's scroll-entry system, since this
 * page doesn't include the dashboard's shared/base.css). No forms, no
 * fetch calls -- every CTA here is a plain link into auth/login or
 * auth/signup.
 * -----------------------------------------------------------------------
 */
document.addEventListener("DOMContentLoaded", function () {
  var fadeEls = document.querySelectorAll("[data-mr-fade]");

  if (fadeEls.length > 0 && "IntersectionObserver" in window) {
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

    fadeEls.forEach(function (el) {
      observer.observe(el);
    });
  } else {
    fadeEls.forEach(function (el) {
      el.classList.add("is-visible");
    });
  }
});
