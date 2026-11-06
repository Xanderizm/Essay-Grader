(function () {
    "use strict";

    var $ = function (s, r) {
        return (r || document).querySelector(s);
    };
    var $$ = function (s, r) {
        return Array.prototype.slice.call((r || document).querySelectorAll(s));
    };
    var csrf = function () {
        var m = $('meta[name="csrf-token"]');
        return m ? m.content : "";
    };
    var NS = "http://www.w3.org/2000/svg";

    /* ---------- 3. Toast ---------- */
    function toast(msg, type) {
        var box = $("#toasts");
        if (!box || !msg) {
            return;
        }
        var t = document.createElement("div");
        t.className = "toast" + (type ? " toast-" + type : "");
        t.textContent = msg;
        box.appendChild(t);
        setTimeout(function () {
            t.classList.add("toast-out");
            setTimeout(function () {
                t.remove();
            }, 250);
        }, 3500);
    }

    /* ---------- Modal konfirmasi kustom (pengganti confirm bawaan browser) ---------- */
    function ask(o) {
        var d = $("#confirm-dialog"),
            ok = $("#cf-ok"),
            cancel = $("#cf-cancel"),
            icon = $("#cf-icon"),
            danger = o.tone === "danger",
            isAlert = o.cancelLabel === false;
        if (!d) {
            return Promise.resolve(window.confirm ? window.confirm(o.text || o.title || "") : true);
        }
        var titleEl = $("#cf-title");
        var textEl = $("#cf-text");
        if (titleEl) titleEl.textContent = o.title || "Konfirmasi";
        if (textEl) textEl.textContent = o.text || "";
        ok.textContent = o.okLabel || "Lanjutkan";
        if (cancel) {
            cancel.hidden = isAlert;
            if (!isAlert) {
                cancel.textContent = o.cancelLabel || "Batalkan";
            }
        }
        ok.className = danger ? "danger" : (o.tone === "primary" ? "btn-lime" : "");
        if (icon) {
            icon.className =
                "dlg-icon dlg-icon-lg " +
                (danger ? "dlg-icon-danger" : (o.tone === "primary" ? "dlg-icon-primary" : "dlg-icon-warn"));
        }
        return new Promise(function (resolve) {
            var result = false;
            ok.onclick = function () {
                result = true;
                d.close();
            };
            if (cancel) {
                cancel.onclick = function () {
                    result = false;
                    d.close();
                };
            }
            d.addEventListener("close", function once() {
                d.removeEventListener("close", once);
                resolve(result);
            });
            d.showModal();
        });
    }

    window.ask = ask;
    window.alert = function (msg) {
        return ask({
            title: "Pemberitahuan",
            text: String(msg),
            okLabel: "Mengerti",
            cancelLabel: false,
            tone: "warning"
        });
    };
    document.addEventListener("submit", function (e) {
        var f = e.target,
            msg = f.getAttribute ? f.getAttribute("data-confirm") : null;
        if (!msg || e.defaultPrevented) {
            return;
        }
        if (f.getAttribute("data-confirmed") === "1") {
            f.removeAttribute("data-confirmed");
            return;
        }
        e.preventDefault();
        ask({
            title: f.getAttribute("data-confirm-title") || "Konfirmasi",
            text: msg,
            okLabel: f.getAttribute("data-confirm-ok") || "Lanjutkan",
            tone: /hapus|permanen|dibuang|diganti/i.test(msg)
                ? "danger"
                : "warning",
        }).then(function (yes) {
            if (yes) {
                f.setAttribute("data-confirmed", "1");
                f.requestSubmit();
            }
        });
    });

    /* ---------- Konfirmasi keluar saat ada penilaian aktif ---------- */
    var logoutForm = $("#logout-form");
    if (logoutForm) {
        logoutForm.addEventListener("submit", function (e) {
            if (logoutForm.getAttribute("data-confirmed") === "1") {
                return;
            }
            if (logoutForm.getAttribute("data-has-active") === "1") {
                e.preventDefault();
                ask({
                    title: "Penilaian Sedang Aktif",
                    text: "Penilaian yang sedang berjalan akan otomatis ditutup jika Anda keluar akun. Lanjutkan keluar?",
                    okLabel: "Tutup & Keluar",
                    cancelLabel: "Batalkan",
                    tone: "warning",
                }).then(function (yes) {
                    if (yes) {
                        logoutForm.setAttribute("data-confirmed", "1");
                        logoutForm.submit();
                    }
                });
            }
        });
    }

    /* ---------- Dialog umum: tombol [data-close] dan klik di luar kotak = tutup ---------- */
    document.addEventListener("click", function (e) {
        var c = e.target.closest ? e.target.closest("[data-close]") : null;
        if (c) {
            var d = c.closest("dialog");
            if (d) {
                d.close();
            }
            return;
        }
        if (e.target.tagName === "DIALOG") {
            e.target.close();
        }
    });

    /* ---------- 5. Grafik sebaran nilai (SVG, tanpa pustaka) ---------- */
    function svg(tag, attrs, text) {
        var n = document.createElementNS(NS, tag);
        Object.keys(attrs || {}).forEach(function (k) {
            n.setAttribute(k, attrs[k]);
        });
        if (text !== undefined && text !== null) {
            n.textContent = text;
        }
        return n;
    }
    function bins(scores) {
        var b = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        scores.forEach(function (s) {
            b[Math.max(0, Math.min(9, Math.floor(s / 10)))]++;
        });
        return b;
    }
    function ticks(max) {
        var top = Math.max(4, max),
            step = top <= 5 ? 1 : Math.ceil(top / 5),
            out = [];
        for (var v = 0; v <= top; v += step) {
            out.push(v);
        }
        if (out[out.length - 1] < max) {
            out.push(out[out.length - 1] + step);
        }
        return out;
    }
    function note(host, text) {
        var p = document.createElement("p");
        p.className = "muted";
        p.textContent = text;
        host.appendChild(p);
    }

    // Kurva halus yang tidak melampaui data (monotone cubic), seperti smoothed area chart
    function monotone(p) {
        var n = p.length,
            dx = [],
            m = [],
            t = [],
            i;
        for (i = 0; i < n - 1; i++) {
            dx[i] = p[i + 1].x - p[i].x;
            m[i] = (p[i + 1].y - p[i].y) / dx[i];
        }
        t[0] = m[0];
        t[n - 1] = m[n - 2];
        for (i = 1; i < n - 1; i++) {
            t[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2;
        }
        for (i = 0; i < n - 1; i++) {
            if (m[i] === 0) {
                t[i] = 0;
                t[i + 1] = 0;
                continue;
            }
            var a1 = t[i] / m[i],
                b1 = t[i + 1] / m[i],
                s = a1 * a1 + b1 * b1;
            if (s > 9) {
                var k = 3 / Math.sqrt(s);
                t[i] = k * a1 * m[i];
                t[i + 1] = k * b1 * m[i];
            }
        }
        var d = "M" + p[0].x + "," + p[0].y;
        for (i = 0; i < n - 1; i++) {
            var h = dx[i] / 3;
            d +=
                " C" +
                (p[i].x + h) +
                "," +
                (p[i].y + t[i] * h) +
                " " +
                (p[i + 1].x - h) +
                "," +
                (p[i + 1].y - t[i + 1] * h) +
                " " +
                p[i + 1].x +
                "," +
                p[i + 1].y;
        }
        return d;
    }

    var Chart = {
        selected: null,
        ro: null,
        timer: null,
        size: "",
        render: function () {
            var host = $("#score-chart"),
                sel = $("#chart-item"),
                dataEl = $("#chart-data");
            if (!host || !dataEl) {
                return;
            }
            var data;
            try {
                data = JSON.parse(dataEl.textContent);
            } catch (e) {
                data = { items: [] };
            }

            if (!data.items.length) {
                if (sel) {
                    sel.hidden = true;
                }
                note(host, "Belum ada pertanyaan yang dinilai.");
                return;
            }
            if (sel) {
                var keep =
                    Chart.selected &&
                    $$("option", sel).some(function (o) {
                        return o.value === String(Chart.selected);
                    });
                if (keep) {
                    sel.value = String(Chart.selected);
                }
                Chart.selected = sel.value;
            }
            var item =
                data.items.filter(function (i) {
                    return String(i.id) === String(Chart.selected);
                })[0] || data.items[0];
            if (!item.scores.length) {
                host.innerHTML = "";
                note(
                    host,
                    "Belum ada nilai untuk " +
                        item.label +
                        ". Grafik muncul setelah jawaban dinilai.",
                );
                return;
            }

            // Cek apakah elemen plot/svg sudah ada sebelumnya untuk transisi smooth langsung pada path
            var plot = $(".plot", host);
            var isNew = !plot;

            if (isNew) {
                host.innerHTML = "";
                plot = document.createElement("div");
                plot.className = "plot";
                plot.style.height = "100%";
                plot.style.minHeight = "140px";
                host.appendChild(plot);
            }

            var W = plot.clientWidth || 320,
                H = plot.clientHeight || 150;
            var L = 16,
                R = 6,
                T = 6,
                B = 18,
                pw = W - L - R,
                ph = H - T - B,
                base = T + ph;
            var b = bins(item.scores),
                tk = ticks(Math.max.apply(null, b)),
                yMax = tk[tk.length - 1];
            var X = function (i) {
                return L + (i + 0.5) * (pw / 10);
            };
            var Y = function (v) {
                return T + ph - (v / yMax) * ph;
            };
            var pts = b.map(function (v, i) {
                return { x: X(i), y: Y(v) };
            });
            var line = monotone(pts);
            var areaPathData =
                line +
                " L" +
                pts[9].x +
                "," +
                base +
                " L" +
                pts[0].x +
                "," +
                base +
                " Z";

            var s = $("svg", plot);
            if (!s) {
                s = svg("svg", {
                    width: W,
                    height: H,
                    viewBox: "0 0 " + W + " " + H,
                    role: "img",
                    "aria-label": "Sebaran nilai " + item.label,
                });

                // CSS Transition agar perubahan path dan circle beranimasi halus (morphing)
                var styleEl = svg(
                    "style",
                    {},
                    "path, circle { transition: d 0.5s ease-in-out, cx 0.5s ease-in-out, cy 0.5s ease-in-out; }",
                );
                s.appendChild(styleEl);

                var defs = svg("defs", {}),
                    g = svg("linearGradient", {
                        id: "areaGrad",
                        x1: 0,
                        y1: 0,
                        x2: 0,
                        y2: 1,
                    });
                g.appendChild(
                    svg("stop", {
                        offset: "0%",
                        "stop-color": "#c4f55d",
                        "stop-opacity": 0.95,
                    }),
                );
                g.appendChild(
                    svg("stop", {
                        offset: "100%",
                        "stop-color": "#c4f55d",
                        "stop-opacity": 0.12,
                    }),
                );
                defs.appendChild(g);
                s.appendChild(defs);

                // Group untuk grid dan teks agar tidak ikut ter-morphing posisinya
                var gridGroup = svg("g", { class: "grid-group" });
                tk.forEach(function (v) {
                    var y = Y(v);
                    gridGroup.appendChild(
                        svg("line", {
                            x1: L,
                            x2: W - R,
                            y1: y,
                            y2: y,
                            stroke: "#e3e3df",
                            "stroke-width": 1,
                        }),
                    );
                    gridGroup.appendChild(
                        svg(
                            "text",
                            {
                                x: L - 3,
                                y: y + 3,
                                "text-anchor": "end",
                                "font-size": 8.5,
                                fill: "#5f5f5a",
                            },
                            v,
                        ),
                    );
                });
                gridGroup.appendChild(
                    svg("line", {
                        x1: L,
                        x2: W - R,
                        y1: base,
                        y2: base,
                        stroke: "#1b1b1b",
                        "stroke-width": 1.5,
                    }),
                );
                var step = pw / 10 < 28 ? 2 : 1;
                b.forEach(function (n, i) {
                    if (i % step === 0) {
                        gridGroup.appendChild(
                            svg(
                                "text",
                                {
                                    x: pts[i].x,
                                    y: base + 12,
                                    "text-anchor": "middle",
                                    "font-size": 8.5,
                                    fill: "#1b1b1b",
                                },
                                i * 10,
                            ),
                        );
                    }
                });
                s.appendChild(gridGroup);

                // Path Area & Line Utama
                var areaPath = svg("path", {
                    class: "chart-area",
                    d: areaPathData,
                    fill: "url(#areaGrad)",
                    stroke: "none",
                });
                var linePath = svg("path", {
                    class: "chart-line",
                    d: line,
                    fill: "none",
                    stroke: "#78b800",
                    "stroke-width": 2,
                    "stroke-linejoin": "round",
                });
                s.appendChild(areaPath);
                s.appendChild(linePath);

                // Group untuk titik-titik (Circles)
                var pointsGroup = svg("g", { class: "points-group" });
                b.forEach(function (n, i) {
                    var dot = svg("circle", {
                        cx: pts[i].x,
                        cy: pts[i].y,
                        r: n > 0 ? 3 : 2,
                        fill: n > 0 ? "#1b1b1b" : "#c9c9c4",
                        "data-index": i,
                    });
                    dot.appendChild(
                        svg(
                            "title",
                            {},
                            i * 10 +
                                "-" +
                                (i === 9 ? 100 : i * 10 + 9) +
                                ": " +
                                n +
                                " responden",
                        ),
                    );
                    pointsGroup.appendChild(dot);
                });
                s.appendChild(pointsGroup);
                plot.appendChild(s);
            } else {
                // Jika SVG sudah ada, cukup update atribut 'd' pada path dan posisi 'cx', 'cy' pada circle agar terjadi animasi smooth (morphing)
                s.setAttribute("aria-label", "Sebaran nilai " + item.label);
                var paths = $$("path", s);
                if (paths.length >= 2) {
                    paths[0].setAttribute("d", areaPathData);
                    paths[1].setAttribute("d", line);
                }
                var circles = $$("circle", s);
                circles.forEach(function (dot, i) {
                    var n = b[i];
                    dot.setAttribute("cx", pts[i].x);
                    dot.setAttribute("cy", pts[i].y);
                    dot.setAttribute("r", n > 0 ? 3 : 2);
                    dot.setAttribute("fill", n > 0 ? "#1b1b1b" : "#c9c9c4");
                    // Update tooltip title
                    var title = $("title", dot);
                    if (title) {
                        title.textContent =
                            i * 10 +
                            "-" +
                            (i === 9 ? 100 : i * 10 + 9) +
                            ": " +
                            n +
                            " responden";
                    }
                });
            }

            Chart.size = W + "x" + H;
            if (window.ResizeObserver) {
                if (Chart.ro) {
                    Chart.ro.disconnect();
                }
                Chart.ro = new ResizeObserver(function () {
                    if (
                        plot.clientWidth + "x" + plot.clientHeight ===
                        Chart.size
                    ) {
                        return;
                    }
                    clearTimeout(Chart.timer);
                    Chart.timer = setTimeout(Chart.render, 120);
                });
                Chart.ro.observe(plot);
            }
        },
    };
    document.addEventListener("change", function (e) {
        if (e.target.id === "chart-item") {
            Chart.selected = e.target.value;
            Chart.render();
        }
    });

    /* ---------- 2. Cari dan urut tabel nilai di browser ---------- */
    var Results = {
        state: { q: "", sort: null, col: null },
        apply: function () {
            var table = $("#results-table");
            if (!table) {
                return;
            }
            var ic = $("#id-col");
            if (ic && Results.state.col) {
                ic.value = Results.state.col;
            }
            if (ic) {
                $$("[data-col]").forEach(function (sp) {
                    sp.hidden =
                        sp.getAttribute("data-col") !== String(ic.value);
                });
            }
            var body = table.tBodies[0],
                rows = $$("tr[data-row]", body);
            var q = Results.state.q.toLowerCase().trim(),
                shown = 0;

            rows.forEach(function (tr) {
                var hit =
                    !q ||
                    (tr.getAttribute("data-search") || "").indexOf(q) > -1;
                tr.hidden = !hit;
                if (hit) {
                    shown++;
                }
            });
            var s = Results.state.sort;
            if (s) {
                rows.sort(function (a, b) {
                    if (s === "nilai") {
                        return (
                            parseFloat(b.getAttribute("data-avg")) -
                            parseFloat(a.getAttribute("data-avg"))
                        );
                    }
                    return (a.getAttribute("data-i-" + s) || "").localeCompare(
                        b.getAttribute("data-i-" + s) || "",
                        "id",
                        { numeric: true },
                    );
                }).forEach(function (tr) {
                    body.appendChild(tr);
                });
            }
            var none = $("#no-match");
            if (none) {
                none.hidden = !(q && shown === 0);
                body.appendChild(none);
            }
        },
        url: function () {
            var u = new URL(location.href);
            ["cari", "urut", "kolom"].forEach(function (k) {
                var v =
                    k === "cari"
                        ? Results.state.q
                        : k === "urut"
                          ? Results.state.sort
                          : Results.state.col;
                if (v) {
                    u.searchParams.set(k, v);
                } else {
                    u.searchParams.delete(k);
                }
            });
            history.replaceState(null, "", u);
        },
        init: function () {
            var qi = $("#cari-input"),
                us = $("#urut"),
                ic = $("#id-col");
            if (ic) {
                Results.state.col = ic.value;
            }
            if (qi) {
                Results.state.q = qi.value;
            }
            if (us) {
                Results.state.sort = us.value;
            }
            Results.apply();
            Chart.render();
        },
    };
    var debounceT;
    document.addEventListener("input", function (e) {
        if (e.target.id !== "cari-input") {
            return;
        }
        clearTimeout(debounceT);
        debounceT = setTimeout(function () {
            Results.state.q = e.target.value;
            Results.apply();
            Results.url();
        }, 150);
    });
    document.addEventListener("change", function (e) {
        if (e.target.id === "urut") {
            Results.state.sort = e.target.value;
            Results.apply();
            Results.url();
        }
        if (e.target.id === "id-col") {
            Results.state.col = e.target.value;
            Results.apply();
            Results.url();
        }
    });
    document.addEventListener("submit", function (e) {
        if (e.target.id === "toolbar-form") {
            e.preventDefault();
        }
    });

    /* ---------- 1. Polling jumlah jawaban dan status (tiap 5 detik) ---------- */
    var Live = {
        root: null,
        url: null,
        version: null,
        fails: 0,
        timer: null,
        ctrl: null,
        delay: 5000,
        start: function () {
            Live.root = $("#live-root");
            if (!Live.root || !Live.root.getAttribute("data-live-url")) {
                return;
            }
            Live.url = Live.root.getAttribute("data-live-url");
            Live.version = Live.root.getAttribute("data-version");
            Live.schedule(Live.delay);
        },
        schedule: function (ms) {
            clearTimeout(Live.timer);
            Live.timer = setTimeout(Live.tick, ms);
        },
        tick: function () {
            if (document.hidden) {
                return Live.schedule(Live.delay);
            }
            if (Live.ctrl) {
                Live.ctrl.abort();
            }
            Live.ctrl = new AbortController();
            fetch(Live.url, {
                headers: { Accept: "application/json" },
                cache: "no-cache",
                credentials: "same-origin",
                signal: Live.ctrl.signal,
            })
                .then(function (r) {
                    if (!r.ok) {
                        throw new Error(r.status);
                    }
                    return r.json();
                })
                .then(function (d) {
                    Live.fails = 0;
                    Live.apply(d);
                    Live.schedule(Live.delay);
                })
                .catch(function (e) {
                    if (e && e.name === "AbortError") {
                        return;
                    }
                    Live.fails++;
                    Live.schedule(
                        Math.min(60000, Live.delay * Math.pow(2, Live.fails)),
                    );
                });
        },
        apply: function (d) {
            $$("[data-live]").forEach(function (el) {
                var k = el.getAttribute("data-live"),
                    v = d[k];
                if (k === "error") {
                    el.textContent = v || "";
                    el.hidden = !v;
                } else if (v !== undefined && v !== null) {
                    el.textContent = v;
                }
            });
            var a = Live.root.getAttribute("data-analysis");
            if (
                a &&
                d.analysis !== a &&
                ["pending", "analyzing"].indexOf(d.analysis) === -1
            ) {
                location.reload();
                return;
            }
            if (
                Live.root.getAttribute("data-live-page") === "results" &&
                Live.version &&
                d.version !== Live.version
            ) {
                Live.refresh(d.version);
            }
        },
        refresh: function (version) {
            var ae = document.activeElement;
            var busy =
                $$("details[open]", Live.root).length > 0 ||
                (ae &&
                    Live.root.contains(ae) &&
                    /INPUT|TEXTAREA|SELECT/.test(ae.tagName));
            if (busy) {
                return;
            }
            Live.swapFrom(location.href, function () {
                Live.version = version;
            });
        },
        swapFrom: function (url, done) {
            fetch(url, {
                headers: { "X-Requested-With": "fetch" },
                credentials: "same-origin",
            })
                .then(function (r) {
                    return r.text();
                })
                .then(function (html) {
                    Live.swapHtml(html);
                    if (done) {
                        done();
                    }
                });
        },
        swapHtml: function (html) {
            var doc = new DOMParser().parseFromString(html, "text/html"),
                fresh = $("#live-root", doc);
            if (!fresh || !Live.root) {
                return;
            }
            Live.root.innerHTML = fresh.innerHTML;
            Live.version = fresh.getAttribute("data-version") || Live.version;
            Results.apply();
            Chart.render();
        },
    };

    /* ---------- 3. Aksi AJAX umum (form[data-ajax]) tanpa pindah halaman ---------- */
    document.addEventListener("submit", function (e) {
        var f = e.target;
        if (e.defaultPrevented || !f.matches || !f.matches("form[data-ajax]")) {
            return;
        }
        e.preventDefault();
        var btn = f.querySelector("button[type=submit]");
        if (btn) {
            btn.disabled = true;
        }
        fetch(f.action, {
            method: "POST",
            body: new FormData(f),
            headers: { "X-Requested-With": "fetch", "X-CSRF-TOKEN": csrf() },
            credentials: "same-origin",
        })
            .then(function (r) {
                if (new URL(r.url).pathname !== location.pathname) {
                    location.href = r.url;
                    return null;
                }
                return r.text();
            })
            .then(function (html) {
                if (html === null) {
                    return;
                }
                var doc = new DOMParser().parseFromString(html, "text/html");
                var flash = $(".flash", doc),
                    err = $("main .error", doc);
                var kind = flash
                    ? flash.classList.contains("flash-error")
                        ? "error"
                        : flash.classList.contains("flash-warning")
                          ? "warn"
                          : "ok"
                    : "error";
                toast(
                    (flash && flash.textContent.trim()) ||
                        (err && err.textContent.trim()) ||
                        "Selesai.",
                    kind,
                );
                Live.swapHtml(html);
            })
            .catch(function () {
                toast("Gagal memproses. Periksa koneksi Anda.", "error");
            })
            .then(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
    });

    /* ---------- Editor penilaian: simpan terpadu, deteksi perubahan, draf, aktivasi ---------- */
    var Editor = {
        init: function () {
            var form = $("#exam-form");
            if (!form) {
                return;
            }
            var fields = $$('[form="exam-form"]').filter(function (el) {
                    return /^(SELECT|TEXTAREA|INPUT)$/.test(el.tagName);
                }),
                saveBtn = $("#save-btn"),
                noteEl = $("#save-note");
            var activateForm = $("#activate-form"),
                dlg = $("#unsaved-dialog"),
                blocked = $("#blocked-dialog");
            var draftKey =
                    "draft:exam:" + (form.getAttribute("data-exam") || "0"),
                wasDirty = false,
                noteT,
                leaving = false;
            var examActive = form.getAttribute("data-active") === "1",
                closeForm = $("#close-form");

            function norm(v) {
                return String(v).replace(/\r\n/g, "\n").trim();
            }
            function dirtyField(el) {
                if (el.tagName === "SELECT") {
                    return Array.prototype.some.call(el.options, function (o) {
                        return o.selected !== o.defaultSelected;
                    });
                }
                // Bandingkan nilai saat ini dengan defaultValue yang bersih dari perbedaan baris
                return norm(el.value) !== norm(el.defaultValue);
            }
            function dirtyCount() {
                return fields.filter(dirtyField).length;
            }
            function modes() {
                return fields.filter(function (el) {
                    return el.hasAttribute("data-mode-toggle");
                });
            }
            function savedValue(sel) {
                var o = Array.prototype.filter.call(sel.options, function (x) {
                    return x.defaultSelected;
                })[0];
                return o ? o.value : sel.value;
            }
            function hasScoring(baseline) {
                return modes().some(function (s) {
                    return (baseline ? savedValue(s) : s.value) === "scoring";
                });
            }
            function setNote(text) {
                if (!noteEl) {
                    return;
                }
                noteEl.textContent = text;
                clearTimeout(noteT);
                if (text) {
                    noteT = setTimeout(function () {
                        noteEl.textContent = "";
                    }, 2500);
                }
            }
            function refresh() {
                var d = dirtyCount() > 0;
                if (saveBtn) {
                    saveBtn.hidden = !d;
                }
                if (wasDirty && !d) {
                    setNote("Tidak ada perubahan");
                }
                wasDirty = d;
                try {
                    var draft = {};
                    fields.forEach(function (el) {
                        if (dirtyField(el)) {
                            draft[el.name] = el.value;
                        }
                    });
                    if (Object.keys(draft).length) {
                        localStorage.setItem(draftKey, JSON.stringify(draft));
                    } else {
                        localStorage.removeItem(draftKey);
                    }
                } catch (e) {}
            }
            function syncMode(sel) {
                var t = document.getElementById(
                    sel.getAttribute("data-mode-toggle"),
                );
                if (t) {
                    t.hidden = sel.value !== "scoring";
                }
            }
            function commitBaseline() {
                fields.forEach(function (el) {
                    if (el.tagName === "SELECT") {
                        Array.prototype.forEach.call(el.options, function (o) {
                            o.defaultSelected = o.selected;
                        });
                    } else {
                        el.defaultValue = el.value;
                    }
                });
                modes().forEach(function (s) {
                    var rg = document.querySelector(
                        '[data-regrade-for="' +
                            s.getAttribute("data-qid") +
                            '"]',
                    );
                    if (rg) {
                        rg.hidden = s.value !== "scoring";
                    }
                });
            }
            function openBlocked() {
                var t = $("#blocked-text");
                if (t) {
                    t.textContent = modes().length
                        ? 'Penilaian belum bisa diaktifkan karena belum ada pertanyaan Paragraph dengan mode "Dinilai". Ubah mode minimal satu pertanyaan Paragraph menjadi "Dinilai", lalu simpan perubahan.'
                        : "Penilaian belum bisa diaktifkan karena formulir ini belum memiliki pertanyaan Paragraph yang dapat dinilai.";
                }
                if (blocked) {
                    blocked.showModal();
                }
            }
            function busy(on) {
                if (saveBtn) {
                    saveBtn.disabled = on;
                }
            }

            function affected() {
                return modes()
                    .filter(function (s) {
                        return (
                            savedValue(s) === "scoring" &&
                            s.value === "non_scoring"
                        );
                    })
                    .reduce(function (n, s) {
                        return (
                            n +
                            (parseInt(
                                s.getAttribute("data-graded") || "0",
                                10,
                            ) || 0)
                        );
                    }, 0);
            }
            function lock() {
                ask({
                    title: "Penilaian sedang aktif",
                    text: "Perubahan tidak bisa disimpan saat penilaian aktif. Tutup penilaian terlebih dulu, simpan perubahan, lalu aktifkan kembali jika perubahan tetap ingin diterapkan.",
                    okLabel: "Tutup penilaian",
                    cancelLabel: "Batalkan",
                    tone: "warning",
                }).then(function (yes) {
                    if (yes && closeForm) {
                        leaving = true;
                        closeForm.submit();
                        return;
                    }
                    if (!yes) {
                        form.reset();
                        modes().forEach(syncMode);
                        refresh();
                    }
                });
            }
            function confirmClear(n, then) {
                ask({
                    title: "Hapus nilai yang sudah ada?",
                    text:
                        'Mengubah pertanyaan ini ke "Tidak dinilai" akan menghapus ' +
                        n +
                        " nilai terkait secara permanen, baik di database maupun di halaman Lihat Nilai. Tindakan ini tidak bisa dibatalkan.",
                    okLabel: "Lanjutkan",
                    cancelLabel: "Batalkan",
                    tone: "danger",
                }).then(function (yes) {
                    if (yes) {
                        then();
                    }
                });
            }

            function save(activate, confirmed) {
                var n = affected();
                if (n > 0 && !confirmed) {
                    return confirmClear(n, function () {
                        save(activate, true);
                    });
                }

                var fd = new FormData(form);
                // Pastikan seluruh elemen dengan atribut form="exam-form" ikut terbawa ke dalam FormData
                $$('[form="exam-form"]').forEach(function (el) {
                    if (el.name && !fd.has(el.name)) {
                        if (el.type === "checkbox" || el.type === "radio") {
                            if (el.checked) fd.append(el.name, el.value);
                        } else {
                            fd.append(el.name, el.value);
                        }
                    }
                });

                fd.set("activate", activate ? "1" : "0");
                fd.set("confirm_clear", confirmed ? "1" : "0");
                busy(true);

                fetch(form.action, {
                    method: "POST",
                    body: fd,
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "fetch",
                        "X-CSRF-TOKEN": csrf(),
                    },
                    credentials: "same-origin",
                })
                    .then(function (r) {
                        return r.json().then(function (d) {
                            return { ok: r.ok, d: d || {} };
                        });
                    })
                    .then(function (x) {
                        if (!x.ok || !x.d.ok) {
                            toast(
                                x.d.message ||
                                    "Gagal menyimpan. Perubahan Anda belum tersimpan.",
                                "error",
                            );
                            return;
                        }
                        leaving = true;
                        try {
                            localStorage.removeItem(draftKey);
                        } catch (e) {}
                        toast("Perubahan berhasil disimpan.", "ok");
                        // Langsung reload halaman agar status bersih dan tombol simpan tersembunyi kembali
                        setTimeout(function () {
                            location.reload();
                        }, 600);
                    })
                    .catch(function () {
                        toast(
                            "Gagal menyimpan. Periksa koneksi Anda.",
                            "error",
                        );
                    })
                    .then(function () {
                        busy(false);
                    });
            }

            function warnActiveEdit() {
                ask({
                    title: "Penilaian Sedang Aktif",
                    text: "Struktur form dan rubrik tidak dapat diubah saat penilaian sedang aktif. Tutup penilaian terlebih dahulu jika ingin melakukan perubahan.",
                    okLabel: "Tutup penilaian",
                    cancelLabel: "Batalkan",
                    tone: "warning",
                }).then(function (yes) {
                    if (yes && closeForm) {
                        leaving = true;
                        closeForm.submit();
                        return;
                    }
                    form.reset();
                    modes().forEach(syncMode);
                    refresh();
                });
            }

            fields.forEach(function (el) {
                el.addEventListener("input", function (e) {
                    if (examActive) {
                        e.preventDefault();
                        warnActiveEdit();
                        return;
                    }
                    refresh();
                });
                el.addEventListener("change", function (e) {
                    if (examActive) {
                        e.preventDefault();
                        warnActiveEdit();
                        return;
                    }
                    refresh();
                });
            });
            modes().forEach(function (s) {
                s.addEventListener("change", function () {
                    if (!examActive) {
                        syncMode(s);
                    }
                });
            });
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                if (examActive) {
                    warnActiveEdit();
                    return;
                }
                save(false);
            });

            var reanalyzeForm = $("#reanalyze-form");
            if (reanalyzeForm) {
                reanalyzeForm.addEventListener("submit", function (e) {
                    if (reanalyzeForm.getAttribute("data-confirmed") === "1") {
                        return;
                    }
                    e.preventDefault();
                    if (examActive) {
                        ask({
                            title: "Penilaian Sedang Aktif",
                            text: "Analisis ulang hanya dapat dilakukan saat penilaian sedang tidak aktif. Tutup penilaian terlebih dahulu jika ingin melakukan analisis ulang.",
                            okLabel: "Tutup penilaian",
                            cancelLabel: "Batalkan",
                            tone: "warning",
                        }).then(function (yes) {
                            if (yes && closeForm) {
                                leaving = true;
                                closeForm.submit();
                            }
                        });
                    } else {
                        ask({
                            title: "Analisis Ulang Formulir",
                            text: "Peran kolom akan diatur ulang otomatis dan perubahan manual Anda akan hilang. Rubrik tetap aman. Lanjutkan?",
                            okLabel: "Lanjutkan",
                            cancelLabel: "Batalkan",
                            tone: "warning",
                        }).then(function (yes) {
                            if (yes) {
                                reanalyzeForm.setAttribute("data-confirmed", "1");
                                reanalyzeForm.submit();
                            }
                        });
                    }
                });
            }

            if (activateForm) {
                activateForm.addEventListener("submit", function (e) {
                    if (e.defaultPrevented) {
                        return;
                    }
                    if (dirtyCount() === 0) {
                        if (!hasScoring(true)) {
                            e.preventDefault();
                            openBlocked();
                        }
                        return;
                    }
                    e.preventDefault();
                    var c = $("#dirty-count");
                    if (c) {
                        c.textContent = dirtyCount();
                    }
                    if (dlg) {
                        dlg.showModal();
                    }
                });
            }
            var bc = $("#dlg-continue"),
                bs = $("#dlg-save");
            if (bc) {
                bc.addEventListener("click", function () {
                    if (dlg) {
                        dlg.close();
                    }
                    if (!hasScoring(true)) {
                        openBlocked();
                        return;
                    }
                    leaving = true;
                    form.reset();
                    modes().forEach(syncMode);
                    refresh();
                    activateForm.submit();
                });
            }
            if (bs) {
                bs.addEventListener("click", function () {
                    if (dlg) {
                        dlg.close();
                    }
                    if (!hasScoring(false)) {
                        openBlocked();
                        return;
                    }
                    save(true);
                });
            }

            try {
                var d = JSON.parse(localStorage.getItem(draftKey) || "null"),
                    n = 0;
                if (d) {
                    fields.forEach(function (el) {
                        if (
                            el.name in d &&
                            norm(el.value) !== norm(d[el.name])
                        ) {
                            el.value = d[el.name];
                            n++;
                        }
                    });
                    modes().forEach(syncMode);
                    var banner = $("#draft-banner");
                    if (n && banner) {
                        banner.hidden = false;
                    }
                }
            } catch (e) {}
            var discard = $("#draft-discard");
            if (discard) {
                discard.addEventListener("click", function () {
                    form.reset();
                    modes().forEach(syncMode);
                    refresh();
                    var b = $("#draft-banner");
                    if (b) {
                        b.hidden = true;
                    }
                });
            }
            document.addEventListener("keydown", function (e) {
                if (
                    (e.ctrlKey || e.metaKey) &&
                    String(e.key).toLowerCase() === "s"
                ) {
                    e.preventDefault();
                    save(false);
                }
            });
            window.addEventListener("beforeunload", function (e) {
                if (!leaving && dirtyCount() > 0) {
                    e.preventDefault();
                    e.returnValue = "";
                }
            });

            wasDirty = dirtyCount() > 0;
            if (saveBtn) {
                saveBtn.hidden = !wasDirty;
            }
        },
    };

    /* ---------- Hapus penilaian: dialog peringatan (juga saat form pengaturan tidak ada) ---------- */
    function deleteDialog() {
        var del = $("#delete-form"),
            dlg = $("#delete-dialog"),
            ok = $("#del-confirm");
        if (!del || !dlg) {
            return;
        }
        del.addEventListener("submit", function (e) {
            e.preventDefault();
            dlg.showModal();
        });
        if (ok) {
            ok.addEventListener("click", function () {
                dlg.close();
                del.submit();
            });
        }
    }

    /* ---------- Teks kolom grid: satu baris, huruf mengecil bila melebihi layar ---------- */
    function fitLines() {
        $$(".fit-line").forEach(function (el) {
            el.style.fontSize = "";
            var size = parseFloat(getComputedStyle(el).fontSize) || 13;
            var host = el.closest(".card") || document.body;
            var limit = Math.min(
                window.innerWidth - 96,
                host.clientWidth * 0.6,
            );
            while (el.getBoundingClientRect().width > limit && size > 9) {
                size -= 0.5;
                el.style.fontSize = size + "px";
            }
        });
    }
    window.addEventListener("resize", fitLines);

    /* ---------- 6. Validasi langsung: link Google Form dan kekuatan password ---------- */
    function formLinks() {
        $$("input[name=form_url]").forEach(function (inp) {
            var hint = document.createElement("p");
            hint.setAttribute("role", "alert");
            hint.hidden = true;
            inp.parentNode.insertBefore(hint, inp.nextSibling);
            function show(m, level) {
                inp.setCustomValidity(m);
                hint.textContent = m;
                hint.hidden = !m;
                hint.className = "hint hint-" + (level || "error");
                inp.classList.toggle("is-invalid", !!m);
            }
            inp.addEventListener("input", function () {
                var v = inp.value.trim();
                if (!v) {
                    return show("");
                }
                if (v.length > 500) {
                    return show("Link Google Form maksimal 500 karakter.");
                }
                if (v.indexOf("https://docs.google.com/forms") !== 0) {
                    return show(
                        'Link harus diawali "https://docs.google.com/forms".',
                    );
                }
                if (/\/forms\/d\/e\//.test(v)) {
                    return show(
                        "Itu link untuk responden (viewform). Buka Form dalam mode edit, lalu salin link yang berisi /edit.",
                        "warning",
                    );
                }
                if (!/\/forms\/d\/[\w-]{20,}/.test(v)) {
                    return show(
                        "Link Google Form tidak lengkap atau tidak dikenali.",
                    );
                }
                show("");
            });
        });
    }
    function strengthMeters() {
        $$("input[data-strength]").forEach(function (inp) {
            var bar = document.createElement("div");
            bar.className = "strength";
            bar.innerHTML = "<i></i><span></span>";
            (inp.closest(".field") || inp.parentNode).appendChild(bar);
            inp.addEventListener("input", function () {
                var v = inp.value,
                    s = 0;
                if (v.length >= 10) {
                    s++;
                }
                if (v.length >= 14) {
                    s++;
                }
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) {
                    s++;
                }
                if (/\d/.test(v)) {
                    s++;
                }
                if (/[^A-Za-z0-9]/.test(v)) {
                    s++;
                }
                var lv = !v
                    ? 0
                    : v.length < 10
                      ? 1
                      : s <= 2
                        ? 2
                        : s <= 3
                          ? 3
                          : 4;
                bar.className = "strength s" + lv;
                bar.querySelector("span").textContent = [
                    "",
                    "Terlalu pendek (minimal 10 karakter)",
                    "Lemah",
                    "Cukup",
                    "Kuat",
                ][lv];
            });
        });
    }

    /* ---------- Mulai ---------- */
    Results.init();
    Editor.init();
    deleteDialog();
    Live.start();
    formLinks();
    strengthMeters();
    fitLines();
    window.toast = toast;
    window.__app = { bins: bins, ticks: ticks, Chart: Chart, Results: Results };
})();
