import "./bootstrap.js";
import "./styles/app.css";

document.addEventListener("DOMContentLoaded", function () {
  const isMobile = window.innerWidth < 992;

  if (isMobile) {
    // SUBMENÚS anidados en mobile
    const submenus = document.querySelectorAll(".navbar-nav .dropdown-submenu");

    submenus.forEach((submenu) => {
      submenu.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.toggle("show");

        // Cierra otros submenús
        submenus.forEach((other) => {
          if (other !== this) {
            other.classList.remove("show");
          }
        });
      });
    });

    // MENU PRINCIPAL: comportamiento toggle
    document.querySelectorAll(".navbar-racklatina .dropdown > a").forEach((el) => {
      el.addEventListener("click", function (e) {
        e.preventDefault();
        const parent = el.closest(".dropdown");
        parent.classList.toggle("show");

        // Cierra otros dropdowns
        document.querySelectorAll(".navbar-racklatina .dropdown").forEach((other) => {
          if (other !== parent) {
            other.classList.remove("show");
          }
        });
      });
    });
  } else {
    // DESKTOP: hover para dropdowns y submenús
    const dropdowns = document.querySelectorAll(".navbar-racklatina .dropdown");
    const submenus = document.querySelectorAll(".dropdown-submenu");

    dropdowns.forEach((dropdown) => {
      dropdown.addEventListener("mouseenter", () => {
        dropdown.querySelector(".dropdown-menu")?.classList.add("show");
      });
      dropdown.addEventListener("mouseleave", () => {
        dropdown.querySelector(".dropdown-menu")?.classList.remove("show");
      });
    });

    submenus.forEach((submenu) => {
      submenu.addEventListener("mouseenter", () => {
        submenu.querySelector(".dropdown-menu")?.classList.add("show");
      });
      submenu.addEventListener("mouseleave", () => {
        submenu.querySelector(".dropdown-menu")?.classList.remove("show");
      });
    });
  }

  // Cierre de dropdowns abiertos cuando se abre uno nuevo (aplica a todos)
  document.querySelectorAll(".navbar .dropdown").forEach((dropdown) => {
    dropdown.addEventListener("show.bs.dropdown", function () {
      document.querySelectorAll(".navbar .dropdown.show").forEach((openDropdown) => {
        if (openDropdown !== dropdown) {
          const toggle = openDropdown.querySelector('[data-bs-toggle="dropdown"]');
          if (toggle) {
            const instance = bootstrap.Dropdown.getInstance(toggle);
            instance?.hide();
          }
        }
      });
    });
  });
});
