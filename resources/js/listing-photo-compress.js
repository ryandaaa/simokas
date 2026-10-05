/**
 * Pure JavaScript / Canvas image compression utility for SIMOKAS.
 * Compresses image files to be <= maxBytes (default 2 MB) before uploading to Livewire.
 */
export async function compressImageFile(file, maxBytes = 2 * 1024 * 1024) {
    if (!file || !(file instanceof File)) {
        return file;
    }

    // If file is already within size limit, return as is
    if (file.size <= maxBytes) {
        return file;
    }

    const mimeType = file.type || 'image/jpeg';
    const supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const targetMime = supportedTypes.includes(mimeType) ? mimeType : 'image/jpeg';

    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onerror = () => reject(new Error('Gagal membaca berkas gambar.'));
        reader.onload = (event) => {
            const img = new Image();
            img.onerror = () => reject(new Error('Gagal memuat gambar ke canvas.'));
            img.onload = async () => {
                let width = img.width;
                let height = img.height;

                // Max dimension constraint (2560px for high-res vehicle photos)
                const maxDimension = 2560;
                if (width > maxDimension || height > maxDimension) {
                    if (width > height) {
                        height = Math.round((height * maxDimension) / width);
                        width = maxDimension;
                    } else {
                        width = Math.round((width * maxDimension) / height);
                        height = maxDimension;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return resolve(file); // fallback to original file if canvas not supported
                }

                ctx.drawImage(img, 0, 0, width, height);

                // Iterative quality reduction until size <= maxBytes
                let quality = 0.88;
                const minQuality = 0.40;

                const attemptCompression = (q) => {
                    canvas.toBlob(
                        (blob) => {
                            if (!blob) {
                                return resolve(file);
                            }

                            if (blob.size <= maxBytes || q <= minQuality) {
                                const extension = targetMime === 'image/webp' ? 'webp' : (targetMime === 'image/png' ? 'png' : 'jpg');
                                const baseName = file.name.replace(/\.[^/.]+$/, '');
                                const compressedFile = new File([blob], `${baseName}.${extension}`, {
                                    type: targetMime,
                                    lastModified: Date.now(),
                                });
                                resolve(compressedFile);
                            } else {
                                attemptCompression(Math.max(minQuality, q - 0.15));
                            }
                        },
                        targetMime,
                        q
                    );
                };

                attemptCompression(quality);
            };

            img.src = event.target.result;
        };

        reader.readAsDataURL(file);
    });
}

// Expose globally for Alpine.js components
if (typeof window !== 'undefined') {
    window.compressImageFile = compressImageFile;
}
