(function () {
  var drawer = document.querySelector("[data-drawer]");
  var search = document.querySelector("[data-search-panel]");
  var openers = document.querySelectorAll("[data-open-drawer]");
  var closers = document.querySelectorAll("[data-close-drawer]");
  var searchBtns = document.querySelectorAll("[data-toggle-search]");

  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle("is-open", open);
    drawer.setAttribute("aria-hidden", open ? "false" : "true");
    document.body.classList.toggle("is-drawer-open", open);
    if (open) {
      var close = drawer.querySelector("[data-close-drawer]");
      if (close) close.focus();
    }
  }
  function setSearch(open) {
    if (!search) return;
    search.classList.toggle("is-open", open);
    document.body.classList.toggle("is-search-open", open);
    search.setAttribute("aria-hidden", open ? "false" : "true");
    if (open) {
      var input = search.querySelector("input[type='search']");
      if (input) input.focus();
    }
  }

  openers.forEach(function (btn) {
    btn.addEventListener("click", function () { setSearch(false); setDrawer(true); });
  });
  closers.forEach(function (btn) {
    btn.addEventListener("click", function () { setDrawer(false); });
  });
  searchBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var willOpen = search && !search.classList.contains("is-open");
      setSearch(willOpen);
    });
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { setDrawer(false); setSearch(false); }
  });

  var chips = document.querySelectorAll("[data-filter]");
  var cards = document.querySelectorAll("[data-category]");
  chips.forEach(function (chip) {
    chip.addEventListener("click", function () {
      var val = chip.getAttribute("data-filter");
      chips.forEach(function (c) {
        c.classList.toggle("is-active", c === chip);
        c.setAttribute("aria-pressed", c === chip ? "true" : "false");
      });
      cards.forEach(function (card) {
        var cats = (card.getAttribute("data-category") || "").split(/\s+/);
        var show = val === "all" || cats.indexOf(val) !== -1;
        card.classList.toggle("is-hidden", !show);
      });
    });
  });
})();
