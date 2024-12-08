jQuery(document).ready(function($) {
    // Initialize backup progress bar
    let progressBar = $('#backup-progress');
    let progressText = $('#backup-progress-text');

    // Handle backup form submission
    $('#backup-form').on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        startBackup(formData);
    });

    // Handle restore confirmation
    $('.restore-backup').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(wpsbAdmin.i18n.confirm_restore)) {
            let backupId = $(this).data('backup-id');
            restoreBackup(backupId);
        }
    });

    // Handle delete confirmation
    $('.delete-backup').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(wpsbAdmin.i18n.confirm_delete)) {
            let backupId = $(this).data('backup-id');
            deleteBackup(backupId);
        }
    });

    function startBackup(formData) {
        $.ajax({
            url: wpsbAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsb_create_backup',
                nonce: wpsbAdmin.nonce,
                ...formData
            },
            beforeSend: function() {
                progressBar.show();
                progressText.text('Initializing backup...');
                updateProgress(0);
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(100);
                    progressText.text('Backup completed successfully!');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showError('Backup failed: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showError('Backup failed: ' + error);
            }
        });
    }

    function restoreBackup(backupId) {
        $.ajax({
            url: wpsbAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsb_restore_backup',
                nonce: wpsbAdmin.nonce,
                backup_id: backupId
            },
            beforeSend: function() {
                progressBar.show();
                progressText.text('Initializing restore...');
                updateProgress(0);
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(100);
                    progressText.text('Restore completed successfully!');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showError('Restore failed: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showError('Restore failed: ' + error);
            }
        });
    }

    function deleteBackup(backupId) {
        $.ajax({
            url: wpsbAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'wpsb_delete_backup',
                nonce: wpsbAdmin.nonce,
                backup_id: backupId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showError('Delete failed: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showError('Delete failed: ' + error);
            }
        });
    }

    function updateProgress(percentage) {
        progressBar.find('.progress-bar').css('width', percentage + '%');
    }

    function showError(message) {
        progressBar.hide();
        $('#backup-error')
            .text(message)
            .show()
            .delay(5000)
            .fadeOut();
    }

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize datetime picker for custom schedule
    if ($.fn.datetimepicker) {
        $('.datetime-picker').datetimepicker({
            format: 'Y-m-d H:i',
            step: 30
        });
    }
});