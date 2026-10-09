/**
 * SIMOKAS Camera Capture & Canvas Watermarking Utility
 * Handles browser camera access (getUserMedia), native mobile fallback,
 * canvas stamping (Job ID and Timestamp), and client-side compression <= 2 MB.
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
        const minQuality = 0.40;

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
        error: null,
        stream: null,
        capturedBlob: null,
        capturedFile: null,
        stampedAt: null,
        itemId: config.itemId || null,
        noPhotoMessage: config.noPhotoMessage || '',

        init() {
            if (!this.formattedTime) {
                const now = new Date();
                this.formattedTime = now.toLocaleString('id-ID', {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                });
            }
        },

        async startCamera() {
            this.error = null;
            this.permissionDenied = false;

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                // Fallback to native input if getUserMedia is not supported
                if (this.$refs.fallbackInput) {
                    this.$refs.fallbackInput.click();
                }
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                    },
                    audio: false,
                });

                this.isStreaming = true;
                this.$nextTick(() => {
                    if (this.$refs.video) {
                        this.$refs.video.srcObject = this.stream;
                        this.$refs.video.play().catch(() => {});
                    }
                });
            } catch (err) {
                this.isStreaming = false;
                this.permissionDenied = true;
                this.permissionMessage = 'Izin kamera ditolak. Harap izinkan akses kamera di pengaturan browser Anda.';
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
            this.isStreaming = false;
        },

        async captureFromVideo() {
            if (!this.$refs.video) return;

            const video = this.$refs.video;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth || 1280;
            canvas.height = video.videoHeight || 720;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const now = new Date();
            const timeStr = now.toLocaleString('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
            this.formattedTime = timeStr;
            this.stampedAt = now.toISOString();

            stampCanvas(canvas, this.jobId, timeStr);

            this.isProcessing = true;
            try {
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
            } finally {
                this.isProcessing = false;
                this.stopCamera();
            }
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

                // Max dimension constraint (2560px)
                const maxDim = 2560;
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

            // Notify Livewire if registered
            if (this.itemId && this.$wire && typeof this.$wire.retakePhoto === 'function') {
                this.$wire.retakePhoto(this.itemId);
            }

            this.stopCamera();
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
                if (typeof this.$wire.applyPhoto === 'function' && this.itemId) {
                    this.$wire.upload(
                        `tempPhotos.${this.itemId}`,
                        this.capturedFile,
                        () => {
                            this.$wire.applyPhoto(this.itemId, this.stampedAt);
                            this.isProcessing = false;
                        },
                        (err) => {
                            this.error = 'Gagal mengunggah foto. Silakan coba lagi.';
                            this.isProcessing = false;
                        }
                    );
                    return;
                }
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
