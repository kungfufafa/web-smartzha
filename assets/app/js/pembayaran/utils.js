/**
 * Payment System Utility Functions
 * Reusable JavaScript functions for payment CRUD operations
 */

const PaymentUtils = (function() {
    'use strict';

    /**
     * Initialize DataTables with CSRF token and common options
     * @param {string} tableId - Table element ID
     * @param {string} ajaxUrl - AJAX endpoint URL
     * @param {Array} columns - DataTables column definitions
     * @param {Object} options - Additional DataTables options
     */
    function initDataTables(tableId, ajaxUrl, columns, options = {}) {
        const defaultOptions = {
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxUrl,
                type: 'POST',
                beforeSend: function(xhr) {
                    if (typeof ajaxcsrf === 'function') {
                        xhr.setRequestHeader('X-CSRF-TOKEN', ajaxcsrf());
                    }
                },
                data: function(d) {
                    return d;
                }
            },
            columns: columns,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            },
            responsive: true,
            dom: '<"top"f>rt<"bottom"p><"clear">>',
            buttons: [
                { extend: 'copy', className: 'btn btn-sm btn-secondary' },
                { extend: 'excel', className: 'btn btn-sm btn-success' },
                { extend: 'pdf', className: 'btn btn-sm btn-danger' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
        };

        return $(tableId).DataTable({...defaultOptions, ...options});
    }

    /**
     * Show loading state with SweetAlert
     */
    function showLoading() {
        Swal.showLoading();
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        Swal.close();
    }

    /**
     * Show success message with toast
     * @param {string} message - Success message
     */
    function showSuccess(message) {
        if (typeof showSuccessToast === 'function') {
            showSuccessToast(message);
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: message
        });
    }

    /**
     * Show warning message
     * @param {string} message - Warning message
     */
    function showWarning(message) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: message
        });
    }

    /**
     * Confirm delete action
     * @param {Function} callback - Function to execute on confirmation
     * @param {string} title - Custom title (default: 'Hapus Data?')
     * @param {string} text - Custom text (default: 'Data yang dihapus tidak dapat dikembalikan.')
     */
    function confirmDelete(callback, title = 'Hapus Data?', text = 'Data yang dihapus tidak dapat dikembalikan.') {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    }

    /**
     * Confirm approve/reject action
     * @param {string} action - 'approve' or 'reject'
     * @param {Function} callback - Function to execute on confirmation
     */
    function confirmVerifikasi(action, callback) {
        const title = action === 'approve' ? 'Setujui Pembayaran?' : 'Tolak Pembayaran?';
        const text = action === 'approve'
            ? 'Pembayaran akan ditandai sebagai LUNAS.'
            : 'Anda yakin ingin menolak pembayaran ini?';

        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
            cancelButtonColor: '#3085d6',
            confirmButtonText: action === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    }

    /**
     * Format currency to IDR
     * @param {number} amount - Amount to format
     * @returns {string} Formatted currency string
     */
    function formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    /**
     * Format date to Indonesian locale
     * @param {string|Date} date - Date to format
     * @returns {string} Formatted date string
     */
    function formatDateIndo(date) {
        const d = new Date(date);
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    /**
     * Validate file upload
     * @param {File} file - File object to validate
     * @param {number} maxSizeMB - Maximum file size in MB (default: 2)
     * @param {Array} allowedTypes - Array of allowed MIME types
     * @returns {Object} Validation result {valid: boolean, message: string}
     */
    function validateFileUpload(file, maxSizeMB = 2, allowedTypes = null) {
        if (!file) {
            return { valid: false, message: 'File belum dipilih' };
        }

        const maxSizeBytes = maxSizeMB * 1024 * 1024;

        // Check file size
        if (file.size > maxSizeBytes) {
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            return {
                valid: false,
                message: `Ukuran file terlalu besar. Maksimal: ${maxSizeMB}MB. File anda: ${fileSizeMB}MB`
            };
        }

        // Check file type if provided
        if (allowedTypes && allowedTypes.length > 0) {
            const fileType = file.type.toLowerCase();
            if (!allowedTypes.includes(fileType)) {
                return {
                    valid: false,
                    message: 'Format file tidak didukung. Hanya file JPG, PNG, atau PDF yang diperbolehkan.'
                };
            }
        }

        return { valid: true, message: '' };
    }

    /**
     * Show file preview
     * @param {File} file - File object to preview
     * @param {string} imgElementId - Image element ID for preview
     * @param {string} containerId - Container element ID to show/hide
     */
    function showFilePreview(file, imgElementId, containerId) {
        const $img = $('#' + imgElementId);
        const $container = $('#' + containerId);

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $img.attr('src', e.target.result);
                $container.show();
            };
            reader.readAsDataURL(file);
        } else {
            $img.attr('src', '').hide();
            $container.hide();
        }
    }

    /**
     * Disable button with loading state
     * @param {jQuery} $btn - Button jQuery element
     * @param {string} text - Loading text (default: 'Memproses...')
     */
    function setButtonLoading($btn, text = 'Memproses...') {
        $btn.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin"></i> ${text}`);
    }

    /**
     * Reset button to normal state
     * @param {jQuery} $btn - Button jQuery element
     * @param {string} originalHtml - Original HTML content
     */
    function resetButton($btn, originalHtml) {
        $btn.prop('disabled', false).html(originalHtml);
    }

    /**
     * AJAX wrapper with common error handling
     * @param {Object} config - jQuery AJAX configuration
     * @returns {Promise} AJAX promise
     */
    function ajax(config) {
        const defaultConfig = {
            dataType: 'json',
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showError('Terjadi kesalahan pada server. Silakan coba lagi.');
            }
        };

        return $.ajax({...defaultConfig, ...config});
    }

    /**
     * Render status badge
     * @param {string} status - Status value
     * @returns {string} HTML for badge
     */
    function renderStatusBadge(status) {
        const badges = {
            'belum_bayar': '<span class="badge badge-danger">Belum Bayar</span>',
            'menunggu_verifikasi': '<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Menunggu Verifikasi</span>',
            'lunas': '<span class="badge badge-success">Lunas</span>',
            'ditolak': '<span class="badge badge-warning">Ditolak</span>',
            'cancelled': '<span class="badge badge-secondary">Dibatalalkan</span>'
        };

        return badges[status] || '<span class="badge badge-secondary">' + status + '</span>';
    }

    /**
     * Get CSRF token for AJAX requests
     * @returns {string} CSRF token
     */
    function getCsrfToken() {
        if (typeof ajaxcsrf === 'function') {
            return ajaxcsrf();
        }
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    // Public API
    return {
        initDataTables,
        showLoading,
        hideLoading,
        showSuccess,
        showError,
        showWarning,
        confirmDelete,
        confirmVerifikasi,
        formatRupiah,
        formatDateIndo,
        validateFileUpload,
        showFilePreview,
        setButtonLoading,
        resetButton,
        ajax,
        renderStatusBadge,
        getCsrfToken
    };

})();
