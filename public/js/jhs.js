/* ==========================================================================
   JOSJIS Hospital System (JHS) — Frontend runtime
   Vanilla JS, tanpa build step (cocok untuk Docker).
   ========================================================================== */
(function () {
  "use strict";

  /* ---------------------------------------------------------------- Toast */
  const TOAST_ICONS = {
    success: "bi-check-circle-fill",
    error: "bi-x-circle-fill",
    warning: "bi-exclamation-triangle-fill",
    info: "bi-info-circle-fill",
  };

  const TOAST_TITLES = {
    success: "Berhasil",
    error: "Gagal",
    warning: "Perhatian",
    info: "Informasi",
  };

  function toastContainer() {
    let el = document.getElementById("jhs-toast-container");
    if (!el) {
      el = document.createElement("div");
      el.id = "jhs-toast-container";
      el.className = "jhs-toast-container";
      el.setAttribute("role", "status");
      el.setAttribute("aria-live", "polite");
      document.body.appendChild(el);
    }
    return el;
  }

  function dismissToast(el) {
    el.classList.add("hiding");
    setTimeout(() => el.remove(), 250);
  }

  window.jhsToast = function (type, message, title) {
    const kind = TOAST_ICONS[type] ? type : "info";
    const el = document.createElement("div");
    el.className = "jhs-toast jhs-toast-" + kind;
    el.innerHTML =
      '<i class="bi ' + TOAST_ICONS[kind] + ' jhs-toast-icon"></i>' +
      '<div class="flex-grow-1">' +
      '<p class="jhs-toast-title">' + escapeHtml(title || TOAST_TITLES[kind]) + "</p>" +
      '<p class="jhs-toast-text">' + escapeHtml(message) + "</p>" +
      "</div>" +
      '<button type="button" class="jhs-toast-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>';

    el.querySelector(".jhs-toast-close").addEventListener("click", () => dismissToast(el));
    toastContainer().appendChild(el);
    setTimeout(() => dismissToast(el), 5200);
  };

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  }

  /* --------------------------------------------------- Sidebar (mobile) */
  function initSidebar() {
    const sidebar = document.getElementById("jhs-sidebar");
    const backdrop = document.getElementById("jhs-sidebar-backdrop");
    const toggles = document.querySelectorAll("[data-sidebar-toggle]");

    if (!sidebar) return;

    function close() {
      sidebar.classList.remove("show");
      if (backdrop) backdrop.classList.remove("show");
    }

    toggles.forEach((btn) =>
      btn.addEventListener("click", () => {
        sidebar.classList.toggle("show");
        if (backdrop) backdrop.classList.toggle("show");
      })
    );

    if (backdrop) backdrop.addEventListener("click", close);
    window.addEventListener("resize", () => {
      if (window.innerWidth >= 992) close();
    });
  }

  /* ------------------------------------------- Confirm dialog (destructive) */
  window.jhsConfirm = function (options) {
    const opts = options || {};
    const title = opts.title || "Konfirmasi Tindakan";
    const message = opts.message || "Apakah Anda yakin ingin melanjutkan tindakan ini?";
    const confirmText = opts.confirmText || "Ya, Lanjutkan";
    const cancelText = opts.cancelText || "Batal";
    const variant = opts.variant || "danger";
    const icon = opts.icon || (variant === "danger" ? "bi-exclamation-triangle" : "bi-question-circle");
    const url = opts.url || "";
    const formId = opts.formId || "";

    let modal = document.getElementById("jhs-confirm-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "jhs-confirm-modal";
      modal.className = "modal fade";
      modal.tabIndex = -1;
      modal.setAttribute("aria-hidden", "true");
      modal.innerHTML =
        '<div class="modal-dialog modal-dialog-centered modal-sm">' +
        '<div class="modal-content">' +
        '<div class="modal-body p-4 text-center">' +
        '<div class="jhs-confirm-icon ' + variant + ' mx-auto mb-3"><i class="bi ' + icon + '"></i></div>' +
        '<h5 class="fw-bold mb-2" data-confirm-title></h5>' +
        '<p class="text-muted-2 mb-4" data-confirm-message></p>' +
        '<div class="d-flex gap-2 justify-content-center">' +
        '<button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal" data-confirm-cancel></button>' +
        '<button type="button" class="btn btn-' + variant + ' btn-sm px-3" data-confirm-ok></button>' +
        "</div>" +
        "</div></div></div>";
      document.body.appendChild(modal);
    }

    modal.querySelector("[data-confirm-title]").textContent = title;
    modal.querySelector("[data-confirm-message]").textContent = message;
    modal.querySelector("[data-confirm-cancel]").textContent = cancelText;

    const okBtn = modal.querySelector("[data-confirm-ok]");
    okBtn.className = "btn btn-" + variant + " btn-sm px-3";
    okBtn.textContent = confirmText;

    const instance = bootstrap.Modal.getOrCreateInstance(modal);
    const handler = function () {
      okBtn.removeEventListener("click", handler);
      modal.removeEventListener("hidden.bs.modal", onHidden);

      if (formId) {
        const form = document.getElementById(formId);
        if (form) {
          const submit = form.querySelector("[type=submit]");
          if (submit) {
            submit.disabled = true;
            submit.textContent = "Memproses...";
          }
          form.submit();
          return;
        }
      }
      if (url) window.location.href = url;
    };
    const onHidden = function () {
      okBtn.removeEventListener("click", handler);
      modal.removeEventListener("hidden.bs.modal", onHidden);
    };

    okBtn.addEventListener("click", handler);
    modal.addEventListener("hidden.bs.modal", onHidden);
    instance.show();
  };

  document.addEventListener("click", function (event) {
    const trigger = event.target.closest("[data-confirm]");
    if (!trigger) return;
    event.preventDefault();

    let payload = {};
    try {
      payload = JSON.parse(trigger.getAttribute("data-confirm") || "{}");
    } catch (e) {
      payload = {};
    }

    // Atribut HTML dipakai sebagai fallback agar tidak wajib menulis JSON inline.
    payload.formId = payload.formId || trigger.getAttribute("data-confirm-form") || "";
    payload.url = payload.url || trigger.getAttribute("data-confirm-url") || "";
    payload.title = payload.title || trigger.getAttribute("data-confirm-title") || "";
    payload.message = payload.message || trigger.getAttribute("data-confirm-message") || "";
    payload.confirmText = payload.confirmText || trigger.getAttribute("data-confirm-text") || "";
    payload.cancelText = payload.cancelText || trigger.getAttribute("data-confirm-cancel-text") || "";
    payload.variant = payload.variant || trigger.getAttribute("data-confirm-variant") || "";

    window.jhsConfirm(payload);
  });

  /* ------------------------------------------------ Auto-submit filter bar */
  function initAutoSubmit() {
    document.querySelectorAll("[data-auto-submit]").forEach((form) => {
      form.querySelectorAll("select, input[type=date], input[type=checkbox]").forEach((field) => {
        field.addEventListener("change", () => form.submit());
      });
    });

    // Debounced search
    document.querySelectorAll("[data-search-submit]").forEach((input) => {
      let timer = null;
      input.addEventListener("input", function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
          const form = input.closest("form");
          if (form) form.submit();
        }, 550);
      });
    });
  }

  /* ----------------------------------------------------- Table filtering */
  function initTableFilter() {
    document.querySelectorAll("[data-table-filter]").forEach((input) => {
      const table = document.querySelector(input.getAttribute("data-table-filter"));
      if (!table) return;
      const targets = table.querySelectorAll("tbody tr");
      input.addEventListener("input", function () {
        const term = input.value.toLowerCase().trim();
        targets.forEach((row) => {
          row.style.display = !term || row.textContent.toLowerCase().includes(term) ? "" : "none";
        });
      });
    });
  }

  /* ---------------------------------------------------------- Global search */
  function initGlobalSearch() {
    const input = document.querySelector("[data-global-search]");
    const results = document.getElementById("jhs-global-search-results");
    if (!input || !results) return;

    let controller = null;
    let timer = null;

    function hide() {
      results.classList.remove("show");
    }

    function render(payload) {
      const groups = payload.data || [];
      if (!groups.length) {
        results.innerHTML = '<div class="p-3 text-center text-muted-2 fs-7">Tidak ada hasil ditemukan.</div>';
        results.classList.add("show");
        return;
      }

      let html = "";
      groups.forEach((group) => {
        html += '<div class="px-2 pt-2 pb-1 fs-8 fw-bold text-uppercase text-muted-2">' + escapeHtml(group.label) + "</div>";
        group.items.forEach((item) => {
          html +=
            '<a class="jhs-global-search-item" href="' + item.url + '">' +
            '<i class="bi ' + (item.icon || "bi-dot") + ' text-primary"></i>' +
            '<div class="flex-grow-1"><div class="fw-semibold small">' + escapeHtml(item.title) + "</div>" +
            '<div class="sub">' + escapeHtml(item.subtitle || "") + "</div></div></a>";
        });
      });
      results.innerHTML = html;
      results.classList.add("show");
    }

    input.addEventListener("input", function () {
      clearTimeout(timer);
      const term = input.value.trim();
      if (term.length < 2) {
        hide();
        return;
      }
      timer = setTimeout(function () {
        if (controller) controller.abort();
        controller = new AbortController();
        fetch(input.getAttribute("data-global-search") + "?q=" + encodeURIComponent(term), {
          signal: controller.signal,
          headers: { Accept: "application/json" },
        })
          .then((r) => r.json())
          .then(render)
          .catch(() => {});
      }, 300);
    });

    document.addEventListener("click", (e) => {
      if (!results.contains(e.target) && e.target !== input) hide();
    });
    input.addEventListener("keydown", (e) => {
      if (e.key === "Escape") hide();
    });
  }

  /* ----------------------------------------------------- Live queue refresh */
  function initLiveRefresh() {
    function refresh(el) {
      if (document.hidden) return;
      fetch(window.location.href, { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then((r) => (r.ok ? r.text() : null))
        .then((html) => {
          if (!html) return;
          const doc = new DOMParser().parseFromString(html, "text/html");
          const fresh = doc.querySelector("[data-live-region]");
          const current = document.querySelector("[data-live-region]");
          if (fresh && current) current.innerHTML = fresh.innerHTML;
        })
        .catch(() => {});
    }

    document.querySelectorAll("[data-live-refresh]").forEach((el) => {
      const seconds = parseInt(el.getAttribute("data-live-refresh"), 10) || 30;
      setInterval(() => refresh(el), seconds * 1000);
    });

    // Tombol segraf manual untuk elemen yang sama.
    document.querySelectorAll("[data-live-refresh-toggle]").forEach((btn) => {
      btn.addEventListener("click", function () {
        const target = document.querySelector(btn.getAttribute("data-live-refresh-toggle"));
        if (target) refresh(target);
      });
    });
  }

  /* -------------------------------------------------------- ApexCharts base */
  window.jhsChartTheme = function (opts) {
    return Object.assign(
      {
        chart: {
          fontFamily: '"Inter","Segoe UI",system-ui,sans-serif',
          toolbar: { show: false },
          animations: { enabled: true, speed: 500 },
        },
        colors: ["#0d6efd", "#0d9488", "#f59e0b", "#ef4444", "#8b5cf6", "#06b6d4", "#ec4899", "#64748b"],
        dataLabels: { enabled: false },
        stroke: { width: 2.5, curve: "smooth" },
        fill: { opacity: 0.18 },
        grid: { borderColor: "#e6ebf2", strokeDashArray: 4, padding: { left: 8, right: 8 } },
        legend: { position: "bottom", fontSize: "12px", markers: { size: 6 }, itemMargin: { horizontal: 8 } },
        tooltip: { theme: "light" },
        noData: { text: "Belum ada data" },
      },
      opts || {}
    );
  };

  /* ------------------------------------------------------- ApexCharts render */
  const CHART_OPTION_KEYS = [
    "chart",
    "colors",
    "dataLabels",
    "stroke",
    "fill",
    "grid",
    "legend",
    "tooltip",
    "plotOptions",
    "xaxis",
    "yaxis",
    "noData",
    "labels",
    "annotations",
  ];

  window.jhsChart = function (canvasId, config, options) {
    const el = document.getElementById(canvasId);
    if (!el || typeof ApexCharts === "undefined") return null;

    const merged = Object.assign({}, config);
    Object.keys(options || {}).forEach((key) => {
      if (CHART_OPTION_KEYS.indexOf(key) === -1) return;
      const value = options[key];
      const current = merged[key];
      merged[key] =
        current && typeof current === "object" && !Array.isArray(current) && value && typeof value === "object"
          ? Object.assign({}, current, value)
          : value;
    });

    const chart = new ApexCharts(el, window.jhsChartTheme(merged));
    chart.render();

    window.jhsCharts = window.jhsCharts || {};
    window.jhsCharts[canvasId] = chart;

    return chart;
  };

  function initTooltips() {
    if (typeof bootstrap === "undefined") return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
  }

  /* --------------------------------------------- Filter chips (status tabs) */
  window.jhsApplyFilter = function (button) {
    const form = button.closest("form") || document.querySelector("[data-filter-form]");
    if (!form) return;
    const input = form.querySelector("[data-filter-input]");
    const value = button.getAttribute("data-filter-chip");
    if (!input) return;

    input.value = input.value === value ? "" : value;

    document.querySelectorAll("[data-filter-chip]").forEach((chip) => {
      const isOn = chip.getAttribute("data-filter-chip") === input.value;
      chip.classList.toggle("btn-primary", isOn);
      chip.classList.toggle("btn-light", !isOn);
      chip.classList.toggle("border", !isOn);
    });

    form.submit();
  };

  /* ------------------------------------------------------------ Print view */
  function initPrint() {
    document.querySelectorAll("[data-print]").forEach((btn) =>
      btn.addEventListener("click", () => window.print())
    );
  }

  /* --------------------------------------------------------------- Confirm password visibility */
  function initPasswordToggle() {
    document.querySelectorAll("[data-toggle-password]").forEach((btn) => {
      btn.addEventListener("click", function () {
        const target = document.querySelector(btn.getAttribute("data-toggle-password"));
        if (!target) return;
        const isPassword = target.type === "password";
        target.type = isPassword ? "text" : "password";
        btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      });
    });
  }

  /* ------------------------------------------------------------------ Boot */
  document.addEventListener("DOMContentLoaded", function () {
    initSidebar();
    initAutoSubmit();
    initTableFilter();
    initGlobalSearch();
    initLiveRefresh();
    initTooltips();
    initPrint();
    initPasswordToggle();

    // Auto-dismiss Bootstrap alert di dalam halaman
    document.querySelectorAll(".alert[data-autodismiss]").forEach((el) => {
      setTimeout(() => {
        if (window.bootstrap && bootstrap.Alert) {
          bootstrap.Alert.getOrCreateInstance(el).close();
        }
      }, 6000);
    });

    // Konfirmasi sebelum submit form yang ditandai
    document.querySelectorAll("form[data-confirm-submit]").forEach((form) => {
      form.addEventListener("submit", function (e) {
        if (form.dataset.confirmed === "1") return;
        e.preventDefault();
        window.jhsConfirm({
          title: form.dataset.confirmTitle || "Konfirmasi",
          message: form.dataset.confirmMessage || "Lanjutkan tindakan ini?",
          confirmText: form.dataset.confirmText || "Ya, Lanjutkan",
          variant: form.dataset.confirmVariant || "warning",
          formId: form.id,
        });
      });
    });
  });
})();
