document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  form.addEventListener("submit", (e) => {
    if (!confirm("Are you sure you want to save changes?")) {
      e.preventDefault();
    }
  });
});
