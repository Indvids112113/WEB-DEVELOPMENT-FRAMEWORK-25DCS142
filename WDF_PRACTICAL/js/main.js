document.addEventListener("DOMContentLoaded", () => {
    fixLinks();
    initThemeToggle();
    initHamburgerMenu();
    initAuthUI();
    initBanner();
    initSlider();
    initModal();
    initDashboardTabs();
    initEvents();
    initFaq();
    initStudentsTable();
    initProfile();
    initAccordion();
    initFormValidation();
});

/* ---------- helpers ---------- */
const esc = (s) => String(s ?? "").replace(/[&<>"']/g, c =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const getUser = () => {
    try { return JSON.parse(localStorage.getItem("studenthub_current_user")); }
    catch { return null; }
};

async function fetchJSON(url) {
    const res = await fetch(url, { credentials: "same-origin" });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || "Request failed");
    return data;
}

function renderPagination(container, total, perPage, current, onChange) {
    const pages = Math.ceil(total / perPage);
    container.innerHTML = "";
    if (pages <= 1) return;
    const mk = (label, page, disabled, active) => {
        const b = document.createElement("button");
        b.className = "page-btn" + (active ? " active-page" : "");
        b.textContent = label;
        b.disabled = disabled;
        b.addEventListener("click", () => onChange(page));
        container.appendChild(b);
    };
    mk("Prev", current - 1, current === 1);
    for (let i = 1; i <= pages; i++) mk(i, i, false, i === current);
    mk("Next", current + 1, current === pages);
}

/* ---------- links: pages are .php, nav says .html ---------- */
function fixLinks() {
    document.querySelectorAll('a[href$=".html"]').forEach(a => {
        a.setAttribute("href", a.getAttribute("href").replace(/\.html$/, ".php"));
    });
}

/* ---------- theme ---------- */
function initThemeToggle() {
    const btn = document.getElementById("themeToggleBtn");
    const body = document.body;
    if (!btn) return;
    const saved = localStorage.getItem("studenthub_theme") || "dark";
    body.classList.toggle("dark-mode", saved !== "light");
    btn.textContent = saved === "light" ? "🌙 Dark" : "☀️ Light";
    btn.addEventListener("click", () => {
        body.classList.toggle("dark-mode");
        const isDark = body.classList.contains("dark-mode");
        localStorage.setItem("studenthub_theme", isDark ? "dark" : "light");
        btn.textContent = isDark ? "☀️ Light" : "🌙 Dark";
    });
}

/* ---------- hamburger ---------- */
function initHamburgerMenu() {
    const btn = document.getElementById("hamburgerBtn");
    const menu = document.getElementById("navMenu");
    if (btn && menu) btn.addEventListener("click", () => menu.classList.toggle("nav-active"));
}

/* ---------- auth badge / role-based nav ---------- */
function initAuthUI() {
    const role = localStorage.getItem("studenthub_role") || "guest";
    const badge = document.getElementById("authBadge");
    if (badge) badge.textContent = "Role: " + role.toUpperCase();

    document.querySelectorAll(".guest-only").forEach(el => el.style.display = role === "guest" ? "" : "none");
    document.querySelectorAll(".student-only").forEach(el => el.style.display = role === "student" ? "" : "none");
    document.querySelectorAll(".admin-only").forEach(el => el.style.display = role === "admin" ? "" : "none");

    const logout = document.getElementById("logoutBtn");
    if (logout) {
        logout.style.display = role === "guest" ? "none" : "";
        logout.addEventListener("click", () => {
            localStorage.removeItem("studenthub_role");
            localStorage.removeItem("studenthub_current_user");
            window.location.href = "logout.php";
        });
    }
}

/* ---------- banner, slider, modal ---------- */
function initBanner() {
    const banner = document.getElementById("notificationBanner");
    const close = document.getElementById("closeBannerBtn");
    if (banner && close) close.addEventListener("click", () => banner.style.display = "none");
}

function initSlider() {
    const slides = document.querySelectorAll(".slide");
    const prev = document.getElementById("prevSlide");
    const next = document.getElementById("nextSlide");
    if (!slides.length) return;
    let i = 0;
    const show = (n) => {
        slides[i].classList.remove("active-slide");
        i = (n + slides.length) % slides.length;
        slides[i].classList.add("active-slide");
    };
    if (prev) prev.addEventListener("click", () => show(i - 1));
    if (next) next.addEventListener("click", () => show(i + 1));
    setInterval(() => show(i + 1), 5000);
}

function initModal() {
    const modal = document.getElementById("infoModal");
    const open = document.getElementById("openModalBtn");
    const close = document.getElementById("closeModalBtn");
    if (!modal) return;
    if (open) open.addEventListener("click", () => modal.classList.add("open"));
    if (close) close.addEventListener("click", () => modal.classList.remove("open"));
    modal.addEventListener("click", e => { if (e.target === modal) modal.classList.remove("open"); });
}

/* ---------- dashboard tabs ---------- */
function initDashboardTabs() {
    const links = document.querySelectorAll(".sidebar-link");
    if (!links.length) return;
    const show = (id) => {
        document.querySelectorAll(".dashboard-section").forEach(s => s.style.display = "none");
        const t = document.getElementById(id);
        if (t) t.style.display = "block";
    };
    links.forEach(l => l.addEventListener("click", e => {
        e.preventDefault();
        show(l.getAttribute("href").substring(1));
    }));
    show("grades");
}

/* ---------- events (from events.php?json=1) ---------- */
function initEvents() {
    const container = document.getElementById("eventsContainer");
    if (!container) return;
    const loader = document.getElementById("eventsLoader");
    const errBox = document.getElementById("eventsError");
    const search = document.getElementById("searchEvents");
    const cat = document.getElementById("filterCategoryEvents");
    const sort = document.getElementById("sortEvents");
    const pager = document.getElementById("eventsPagination");
    const PER_PAGE = 6;
    let all = [], page = 1;

    const render = () => {
        const q = search.value.toLowerCase();
        let list = all.filter(e =>
            e.title.toLowerCase().includes(q) &&
            (cat.value === "All" || e.category === cat.value));
        if (sort.value === "dateAsc") list.sort((a, b) => a.event_date.localeCompare(b.event_date));
        if (sort.value === "dateDesc") list.sort((a, b) => b.event_date.localeCompare(a.event_date));
        if (sort.value === "titleAsc") list.sort((a, b) => a.title.localeCompare(b.title));

        const start = (page - 1) * PER_PAGE;
        const slice = list.slice(start, start + PER_PAGE);
        container.innerHTML = slice.length ? slice.map(e => `
            <article>
                <h3>${esc(e.title)}</h3>
                <p><strong>Date:</strong> ${esc(e.event_date)}</p>
                <p><strong>Location:</strong> ${esc(e.location)}</p>
                <p><strong>Category:</strong> ${esc(e.category)}</p>
                <p>${esc(e.description)}</p>
            </article>`).join("") : '<p class="empty-state">No events found.</p>';
        renderPagination(pager, list.length, PER_PAGE, page, p => { page = p; render(); });
    };

    [search, cat, sort].forEach(el => el.addEventListener(el === search ? "input" : "change", () => { page = 1; render(); }));

    fetchJSON("events.php?json=1")
        .then(data => { all = data; loader.style.display = "none"; render(); })
        .catch(err => {
            loader.style.display = "none";
            errBox.style.display = "block";
            errBox.textContent = "Could not load events: " + err.message;
        });
}

/* ---------- FAQ (static data) ---------- */
function initFaq() {
    const container = document.getElementById("faqContainer");
    if (!container) return;
    const faqs = [
        { c: "Account", q: "How do I register?", a: "Open the Register page, fill in your details and submit the form." },
        { c: "Account", q: "I forgot my password. What now?", a: "Contact support at support@studenthub.edu to reset it." },
        { c: "Academic", q: "Where can I see my grades?", a: "Log in and open My Grades on the Dashboard." },
        { c: "Library", q: "How long can I keep a book?", a: "Books are issued for 14 days and can be renewed once." },
        { c: "Events", q: "How do I register for an event?", a: "Log in, open the Events page and use the Register button." },
        { c: "Financial", q: "How do I pay my fees?", a: "Fees can be paid at the accounts office or through the university portal." },
        { c: "Hostel", q: "How do I apply for a hostel room?", a: "Submit the hostel form to the warden's office before the semester starts." }
    ];
    const loader = document.getElementById("faqLoader");
    const search = document.getElementById("searchFaq");
    const cat = document.getElementById("filterCategoryFaq");

    const render = () => {
        const q = search.value.toLowerCase();
        const list = faqs.filter(f =>
            (f.q.toLowerCase().includes(q) || f.a.toLowerCase().includes(q)) &&
            (cat.value === "All" || f.c === cat.value));
        container.innerHTML = list.length ? list.map(f => `
            <div class="accordion-item">
                <button class="accordion-header">${esc(f.q)} <span>+</span></button>
                <div class="accordion-body"><p>${esc(f.a)}</p></div>
            </div>`).join("") : '<p class="empty-state">No questions found.</p>';
    };
    search.addEventListener("input", render);
    cat.addEventListener("change", render);
    if (loader) loader.style.display = "none";
    render();
}

function initAccordion() {
    const c = document.getElementById("faqContainer");
    if (!c) return;
    c.addEventListener("click", e => {
        const h = e.target.closest(".accordion-header");
        if (!h) return;
        const body = h.nextElementSibling;
        const wasOpen = body.classList.contains("open");
        c.querySelectorAll(".accordion-body").forEach(b => b.classList.remove("open"));
        if (!wasOpen) body.classList.add("open");
    });
}

/* ---------- admin students table (from admin.php?json=1) ---------- */
function initStudentsTable() {
    const body = document.getElementById("studentsTableBody");
    if (!body) return;
    const table = document.getElementById("studentsTable");
    const loader = document.getElementById("studentsLoader");
    const errBox = document.getElementById("studentsError");
    const search = document.getElementById("searchStudents");
    const status = document.getElementById("filterStatusStudents");
    const pager = document.getElementById("studentsPagination");
    const PER_PAGE = 10;
    let all = [], page = 1;

    const render = () => {
        const q = search.value.toLowerCase();
        const list = all.filter(s =>
            (s.name.toLowerCase().includes(q) || String(s.id).toLowerCase().includes(q)) &&
            (status.value === "All" || s.status === status.value));
        const slice = list.slice((page - 1) * PER_PAGE, page * PER_PAGE);
        body.innerHTML = slice.length ? slice.map(s => `
            <tr>
                <td>${esc(s.id)}</td><td>${esc(s.name)}</td><td>${esc(s.course)}</td>
                <td>Year ${esc(s.year)}</td>
                <td><span class="status-badge status-${esc(String(s.status).toLowerCase())}">${esc(s.status)}</span></td>
            </tr>`).join("") : '<tr><td colspan="5" class="empty-state">No students found.</td></tr>';
        renderPagination(pager, list.length, PER_PAGE, page, p => { page = p; render(); });
    };
    search.addEventListener("input", () => { page = 1; render(); });
    status.addEventListener("change", () => { page = 1; render(); });

    fetchJSON("admin.php?json=1")
        .then(data => { all = data; loader.style.display = "none"; table.style.display = ""; render(); })
        .catch(err => {
            loader.style.display = "none";
            errBox.style.display = "block";
            errBox.textContent = "Could not load students: " + err.message;
        });
}

/* ---------- profile ---------- */
function initProfile() {
    const name = document.getElementById("profileName");
    if (!name) return;
    const u = getUser();
    if (!u) {
        name.textContent = "Please log in to view your profile.";
        document.getElementById("profileId").textContent = "-";
        document.getElementById("profileMajor").textContent = "-";
        return;
    }
    name.textContent = u.name;
    document.getElementById("profileId").textContent = u.id;
    document.getElementById("profileMajor").textContent = `${u.course} (Year ${u.year})`;
}

/* ---------- password strength ---------- */
function initFormValidation() {
    const input = document.getElementById("password");
    const bar = document.getElementById("strengthBar");
    const text = document.getElementById("strengthText");
    if (!input || !bar || !text) return;
    input.addEventListener("input", () => {
        const v = input.value;
        let score = 0;
        if (v.length >= 8) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^a-zA-Z0-9]/.test(v)) score++;
        if (!v.length) { bar.style.width = "0%"; text.textContent = ""; return; }
        const [w, c, t] = score <= 1 ? ["33%", "#ef4444", "Weak Password"]
            : score <= 3 ? ["66%", "#f59e0b", "Medium Strength"]
            : ["100%", "#10b981", "Strong Password"];
        bar.style.width = w; bar.style.backgroundColor = c;
        text.textContent = t; text.style.color = c;
    });
}