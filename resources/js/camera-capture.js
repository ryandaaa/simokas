/**
 * SIMOKAS Camera Capture & Canvas Watermarking Utility
 * Single native capture input (capture="environment"): opens the device camera
 * app on mobile, file picker on desktop. Canvas stamping (Job ID and
 * Timestamp), and client-side compression <= 2 MB.
 */

export function stampCanvas(canvas, jobId, formattedTime) {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const width = canvas.width;
    const height = canvas.height;

    // Proportional banner height and font size
    const bannerHeight = Math.max(48, Math.round(height * 0.08));
    const fontSize = Math.max(14, Math.round(bannerHeight * 0.38));

    // Dark banner at bottom
    ctx.fillStyle = 'rgba(15, 23, 42, 0.80)';
    ctx.fillRect(0, height - bannerHeight, width, bannerHeight);

    // Text styling
    ctx.fillStyle = '#FFFFFF';
    ctx.font = `600 ${fontSize}px system-ui, -apple-system, sans-serif`;
    ctx.textBaseline = 'middle';

    const yPosition = height - (bannerHeight / 2);
    const padding = Math.max(16, Math.round(width * 0.03));

    // Left: Job ID
    if (jobId) {
        ctx.textAlign = 'left';
        ctx.fillText(`ID Job: ${jobId}`, padding, yPosition);
    }

    // Right: Timestamp
    if (formattedTime) {
        ctx.textAlign = 'right';
        ctx.fillText(formattedTime, width - padding, yPosition);
    }
}

export async function compressCanvasToBlob(canvas, maxBytes = 2 * 1024 * 1024, targetMime = 'image/jpeg') {
    return new Promise((resolve) => {
        let quality = 0.88;
        const minQuality = 0.30;

        const attempt = (q) => {
            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        return resolve(null);
                    }
                    if (blob.size <= maxBytes || q <= minQuality) {
                        resolve(blob);
                    } else {
                        attempt(Math.max(minQuality, q - 0.15));
                    }
                },
                targetMime,
                q
            );
        };

        attempt(quality);
    });
}

export function cameraCapture(config = {}) {
    return {
        jobId: config.jobId || '',
        formattedTime: config.formattedTime || '',
        previewUrl: config.initialPhotoUrl || null,
        permissionDenied: Boolean(config.hasInitialPermissionDenied),
        permissionMessage: '',
        isStreaming: false,
        isProcessing: false,
        uploadProgress: 0,
        uploadGuard: null,
        error: null,
        stream: null,
        capturedBlob: null,
        capturedFile: null,
        stampedAt: null,
        itemId: config.itemId || null,
        hasSavedPhoto: Boolean(config.hasSavedPhoto),
        noPhotoMessage: config.noPhotoMessage || '',
        cameraNotReadyMessage: config.cameraNotReadyMessage || '',

        init() {
            if (!this.formattedTime) {
                const now = new Date();
                this.formattedTime = now.toLocaleString('id-ID', {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                });
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
            this.isStreaming = false;
        },

        async handleFileInput(event) {
            const input = event.target;
            if (!input.files || input.files.length === 0) return;

            const rawFile = input.files[0];
            this.isProcessing = true;
            this.error = null;

            try {
                const img = new Image();
                const objectUrl = URL.createObjectURL(rawFile);

                await new Promise((resolve, reject) => {
                    img.onload = () => resolve();
                    img.onerror = () => reject(new Error('Gagal membaca gambar.'));
                    img.src = objectUrl;
                });

                URL.revokeObjectURL(objectUrl);

                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // Batas dimensi (1920px) agar berkas HP selalu kecil dan unggahan cepat via LAN.
                const maxDim = 1920;
                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) throw new Error('Canvas 2D context tidak didukung.');

                ctx.drawImage(img, 0, 0, width, height);

                const now = new Date();
                const timeStr = now.toLocaleString('id-ID', {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                });
                this.formattedTime = timeStr;
                this.stampedAt = now.toISOString();

                stampCanvas(canvas, this.jobId, timeStr);

                const blob = await compressCanvasToBlob(canvas);
                if (blob) {
                    this.capturedBlob = blob;
                    this.capturedFile = new File([blob], `capture-${Date.now()}.jpg`, {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });
                    this.capturedFile.isCameraCapture = true;

                    if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    this.previewUrl = URL.createObjectURL(blob);
                }
            } catch (err) {
                this.error = err.message || 'Gagal memproses foto.';
            } finally {
                this.isProcessing = false;
                // Clear input so selecting again triggers event
                input.value = '';
            }
        },

        retake() {
            if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = null;
            this.capturedBlob = null;
            this.capturedFile = null;
            this.error = null;

            // Hanya hubungi server bila foto tersimpan benar-benar ada. Panggilan
            // yang tidak perlu me-render ulang Livewire dan merusak instance Alpine
            // yang masih hidup (tombol kamera ikut mati).
            if (this.hasSavedPhoto && this.itemId && this.$wire && typeof this.$wire.retakePhoto === 'function') {
                this.$wire.retakePhoto(this.itemId);
                this.stopCamera();
                return;
            }

            // Pratinjau baru (belum tersimpan): kembali ke tombol Ambil Foto.
        },

        // Cegah reload tidak sengaja (mis. pull-to-refresh) selama berkas diunggah:
        // reload di tengah jalan membuat respons sukses jadi yatim dan UI basi selamanya.
        startUploadGuard() {
            this.stopUploadGuard();
            this.uploadGuard = (e) => {
                e.preventDefault();
            };
            window.addEventListener('beforeunload', this.uploadGuard);
        },

        stopUploadGuard() {
            if (this.uploadGuard) {
                window.removeEventListener('beforeunload', this.uploadGuard);
                this.uploadGuard = null;
            }
        },

        usePhoto() {
            // Cegah tap ganda selama upload berjalan.
            if (this.isProcessing) return;

            // Jangan pernah diam: beri tahu user bila tidak ada berkas foto baru.
            if (!this.capturedFile) {
                this.error = this.noPhotoMessage || 'Belum ada foto baru.';
                return;
            }

            // If inside Livewire component
            if (this.$wire) {
                this.isProcessing = true;
                this.uploadProgress = 0;
                this.startUploadGuard();
                if (typeof this.$wire.applyPhoto === 'function' && this.itemId) {
                    this.$wire.upload(
                        `tempPhotos.${this.itemId}`,
                        this.capturedFile,
                        () => {
                            this.$wire.applyPhoto(this.itemId, this.stampedAt);
                            this.isProcessing = false;
                            this.uploadProgress = 0;
                            this.stopUploadGuard();
                            // Jaring pengaman: bila render sesudah-simpan tidak teraplikasi
                            // (respons hilang di jaringan lambat), sinkron paksa sekali.
                            // Kasus normal: cabang sudah berganti (root lepas) = no-op.
                            const pendingBlob = this.previewUrl;
                            const component = this;
                            setTimeout(() => {
                                const root = component.$el;
                                if (!root || !root.isConnected) return;
                                if (!pendingBlob || !pendingBlob.startsWith('blob:')) return;
                                if (component.previewUrl !== pendingBlob) return;
                                if (!root.querySelector('img[src^="blob:"]')) return;
                                if (component.$wire && typeof component.$wire.$refresh === 'function') {
                                    component.$wire.$refresh();
                                }
                            }, 5000);
                        },
                        (err) => {
                            this.error = 'Gagal mengunggah foto. Silakan coba lagi.';
                            this.isProcessing = false;
                            this.uploadProgress = 0;
                            this.stopUploadGuard();
                        },
                        (event) => {
                            const pct = event && event.detail && typeof event.detail.progress === 'number'
                                ? Math.round(event.detail.progress)
                                : 0;
                            this.uploadProgress = Math.min(100, Math.max(0, pct));
                        }
                    );
                    return;
                }
                this.isProcessing = false;
                this.uploadProgress = 0;
                this.stopUploadGuard();
            }

            this.$dispatch('photo-ready', {
                file: this.capturedFile,
                stampedAt: this.stampedAt,
                itemId: this.itemId,
            });
        },
    };
}

if (typeof window !== 'undefined') {
    window.stampCanvas = stampCanvas;
    window.compressCanvasToBlob = compressCanvasToBlob;
    window.cameraCapture = cameraCapture;
}
