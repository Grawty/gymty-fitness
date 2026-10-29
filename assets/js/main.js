document.addEventListener("DOMContentLoaded", () => {
  const navToggle = document.getElementById("navToggle");
  const navMenu = document.getElementById("navMenu");

  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navToggle.classList.toggle("active");
      navMenu.classList.toggle("open");
      document.body.style.overflow = navMenu.classList.contains("open")
        ? "hidden"
        : "";
    });

    navMenu.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        navToggle.classList.remove("active");
        navMenu.classList.remove("open");
        document.body.style.overflow = "";
      });
    });
  }

  const navbar = document.getElementById("navbar");
  if (navbar) {
    window.addEventListener("scroll", () => {
      navbar.classList.toggle("scrolled", window.scrollY > 60);
    });
  }

  const counters = document.querySelectorAll("[data-count]");
  if (counters.length) {
    const animateCounter = (el) => {
      const target = parseInt(el.getAttribute("data-count"), 10);
      const suffix = el.getAttribute("data-suffix") || "";
      const duration = 2000;
      const step = Math.max(1, Math.floor(target / (duration / 16)));
      let current = 0;

      const tick = () => {
        current += step;
        if (current >= target) {
          el.textContent = target.toLocaleString("tr-TR") + suffix;
          return;
        }
        el.textContent = current.toLocaleString("tr-TR") + suffix;
        requestAnimationFrame(tick);
      };
      tick();
    };

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 },
    );

    counters.forEach((c) => observer.observe(c));
  }

  const fadeElements = document.querySelectorAll(".fade-up");
  if (fadeElements.length) {
    const fadeObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            fadeObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1 },
    );

    fadeElements.forEach((el) => fadeObserver.observe(el));
  }

  const flash = document.getElementById("flashMsg");
  if (flash) {
    setTimeout(() => {
      flash.style.transition = "opacity 0.4s ease, transform 0.4s ease";
      flash.style.opacity = "0";
      flash.style.transform = "translateY(-10px)";
      setTimeout(() => flash.remove(), 400);
    }, 4000);
  }

  document.querySelectorAll("form[data-validate]").forEach((form) => {
    form.addEventListener("submit", function (e) {
      let valid = true;
      const required = form.querySelectorAll("[required]");

      required.forEach((input) => {
        input.style.borderColor = "";

        if (!input.value.trim()) {
          input.style.borderColor = "#ff4b5c";
          valid = false;
        }

        if (input.type === "email" && input.value.trim()) {
          const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRe.test(input.value.trim())) {
            input.style.borderColor = "#ff4b5c";
            valid = false;
          }
        }
      });

      const pass = form.querySelector('input[name="password"]');
      const confirm = form.querySelector('input[name="password_confirm"]');
      if (pass && confirm && pass.value !== confirm.value) {
        confirm.style.borderColor = "#ff4b5c";
        valid = false;
      }

      if (!valid) {
        e.preventDefault();
      }
    });
  });

  const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
  };

  const savedTheme = getCookie("gymty-theme") || "default";
  document.body.setAttribute("data-theme", savedTheme);

  document.querySelectorAll(".theme-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const theme = e.target.getAttribute("data-theme");
      document.body.setAttribute("data-theme", theme);
      document.cookie = `gymty-theme=${theme}; path=/; max-age=31536000`;
    });
  });

  const phoneInputs = document.querySelectorAll('input[name="phone"]');
  phoneInputs.forEach((input) => {
    input.addEventListener("input", function (e) {
      let x = e.target.value.replace(/\D/g, "");

      if (x.length === 0) {
        e.target.value = "";
        return;
      }

      if (x[0] !== "0") {
        x = "0" + x;
      }

      x = x.substring(0, 11);

      let res = "";
      if (x.length > 0) {
        res += x.substring(0, 4);
      }
      if (x.length > 4) {
        res += " " + x.substring(4, 7);
      }
      if (x.length > 7) {
        res += " " + x.substring(7, 9);
      }
      if (x.length > 9) {
        res += " " + x.substring(9, 11);
      }

      e.target.value = res;
    });

    input.addEventListener("focus", function (e) {
      if (e.target.value === "") {
        e.target.value = "0";
      }
    });
  });
});
