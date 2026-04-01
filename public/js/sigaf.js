/* ============================================
   SIGAF - JavaScript Principal
   ============================================ */

// === MODAL FUNCTIONS ===
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Fermer modal en cliquant sur l'overlay
document.querySelectorAll('.modal-overlay').forEach(function(m) {
    m.addEventListener('click', function(e) {
        if (e.target === m) m.classList.remove('open');
    });
});

// === TAB SWITCHER ===
function switchTab(btn, id) {
    var tabs = btn.closest('.tabs').querySelectorAll('.tab-btn');
    tabs.forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');

    var bodies = btn.closest('.panel').querySelectorAll('.panel-body');
    bodies.forEach(function(b) { b.style.display = 'none'; });
    document.getElementById(id).style.display = 'block';
}

// === TOAST NOTIFICATIONS ===
function showToast(msg, type) {
    type = type || 'info';
    var container = document.getElementById('toastContainer');
    var icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        info: 'bi-info-circle-fill'
    };
    var colors = {
        success: 'var(--ac2)',
        error: 'var(--warn)',
        info: 'var(--ac)'
    };

    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<i class="bi ' + icons[type] + '" style="color:' + colors[type] + ';font-size:1.1rem"></i><span>' + msg + '</span>';
    container.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3500);
}

// === MOBILE MENU TOGGLE ===
document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }
});

// === CHART (Dashboard) ===
var chartData = {
    2026: [180, 240, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    2025: [150, 200, 320, 280, 410, 350, 290, 380, 420, 310, 260, 390]
};
var barColors = ['#2E7D32','#388E3C','#43A047','#2E7D32','#0288D1','#0288D1','#D4A017','#D4A017','#2E7D32','#0288D1','#D32F2F','#2E7D32'];
var months = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];

function updateChart(year) {
    var data = chartData[year] || chartData[2026];
    var max = Math.max.apply(null, data.concat([1]));
    var chartArea = document.getElementById('chartArea');
    var chartLabels = document.getElementById('chartLabels');

    if (!chartArea) return;

    var barsHtml = '';
    var labelsHtml = '';

    for (var i = 0; i < data.length; i++) {
        var pct = Math.max((data[i] / max) * 100, 3);
        var opacity = data[i] === 0 ? 0.2 : 1;
        var value = data[i] > 0 ? data[i] + 'K' : '—';
        barsHtml += '<div class="chart-bar" style="height:' + pct + '%;background:' + barColors[i] + ';opacity:' + opacity + '"><span class="tv">' + value + '</span></div>';
    }

    for (var j = 0; j < months.length; j++) {
        labelsHtml += '<span>' + months[j] + '</span>';
    }

    chartArea.innerHTML = barsHtml;
    chartLabels.innerHTML = labelsHtml;
}

// Init chart on page load
document.addEventListener('DOMContentLoaded', function() {
    var yearSelect = document.getElementById('chartYearSelect');
    if (yearSelect) {
        updateChart(yearSelect.value);
        yearSelect.addEventListener('change', function() {
            updateChart(this.value);
        });
    }
});
