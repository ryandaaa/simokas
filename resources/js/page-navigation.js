/**
 * SIMOKAS Page Navigation & Loading Bar
 * Memberikan visual progress bar tipis di bagian atas layar (seperti YouTube/GitHub)
 * serta transisi halus saat berpindah halaman dengan wire:navigate.
 */

let progressTimer = null;
let trickler = null;

function getProgressBar() {
    let container = document.getElementById('simokas-progress-bar');
    if (!container) {
        container = document.createElement('div');
        container.id = 'simokas-progress-bar';
        const inner = document.createElement('div');
        inner.className = 'bar';
        container.appendChild(inner);
        document.body.prepend(container);
    }
    return {
        container,
        bar: container.querySelector('.bar'),
    };
}

function startNavigationProgress() {
    const { container, bar } = getProgressBar();
    if (!bar) return;

    clearInterval(trickler);
    clearTimeout(progressTimer);

    // Reset posisi bar secara instan
    bar.style.transition = 'none';
    bar.style.width = '0%';
    container.classList.add('active');

    // Trigger layout reflow
    void bar.offsetWidth;

    // Gerak cepat ke 30%
    bar.style.transition = 'width 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
    bar.style.width = '30%';

    // Bergerak merayap (trickle) halus menuju ~80% menunggu response server
    let current = 30;
    trickler = setInterval(() => {
        if (current < 80) {
            current += Math.random() * 8 + 2;
            bar.style.transition = 'width 0.35s ease-out';
            bar.style.width = `${Math.min(current, 85)}%`;
        }
    }, 200);

    // Redupkan sedikit konten lama agar pengguna tahu klik direspon instan
    const main = document.getElementById('main-content');
    if (main) {
        main.classList.add('page-navigating');
    }
}

function finishNavigationProgress() {
    const { container, bar } = getProgressBar();
    if (!bar) return;

    clearInterval(trickler);
    clearTimeout(progressTimer);

    // Luncurkan ke 100%
    bar.style.transition = 'width 0.15s ease-out';
    bar.style.width = '100%';

    // Fade out bar setelah mencapai 100%
    progressTimer = setTimeout(() => {
        container.classList.remove('active');
        setTimeout(() => {
            bar.style.transition = 'none';
            bar.style.width = '0%';
        }, 200);
    }, 180);

    // Hapus kelas peredup dan beri efek fade-in halus pada konten baru
    const main = document.getElementById('main-content');
    if (main) {
        main.classList.remove('page-navigating');
        main.classList.remove('animate-page-enter');
        void main.offsetWidth;
        main.classList.add('animate-page-enter');
    }
}

// Event listener navigasi Livewire / Alpine
document.addEventListener('livewire:navigating', startNavigationProgress);
document.addEventListener('livewire:navigated', finishNavigationProgress);
document.addEventListener('alpine:navigating', startNavigationProgress);
document.addEventListener('alpine:navigated', finishNavigationProgress);

// Kembali ke paling atas setiap ganti kategori inspeksi (di-dispatch dari InspectionFormPage).
// Sengaja vanilla di window (bukan x-data di root Livewire): x-data di root menjadikan
// komponen Alpine foto sebagai anak sehingga state pratinjau ikut tereset saat morph.
function scrollInspectionToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('inspection-category-changed', scrollInspectionToTop);

function registerInspectionScrollListener() {
    if (window.Livewire && typeof window.Livewire.on === 'function') {
        window.Livewire.on('inspection-category-changed', scrollInspectionToTop);
    }
}

if (window.Livewire) {
    registerInspectionScrollListener();
} else {
    document.addEventListener('livewire:init', registerInspectionScrollListener);
}
