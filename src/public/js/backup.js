_$.ready(function ($) {
    var btn = $('#btn-backup-system'),
        msg = $('#backup-system-message');
    btn.on('click', function () {
        btn.prop('disabled', true);
        $.http.post(btn.data('url'), {}, function () {
            msg.prop('hidden', false);
        });
    });

    $('.btn-backup-remove').on('click', function () {
        var el = $(this);
        UIkit.modal.confirm($hb.language._('backup-remove-confirm', {file: el.data('file')})).then(
            function () {
                UIkit.notification('<span uk-spinner></span> ' + $hb.language._('please-wait-msg'), {timeout: 30000});
                $.http.post(el.data('url'), {backupFile: el.data('file')}, function (response) {
                    UIkit.notification.closeAll();
                    UIkit.notification(response.message, {status: response.status});

                    if (response.status === 'success') {
                        el.parent('li').remove();
                    }
                });
            },
            function () {
            }
        );
    });

    $('.btn-backup-restore').on('click', function () {
        var el = $(this);
        UIkit.modal.confirm($hb.language._('backup-restore-confirm', {file: el.data('file')})).then(
            function () {
                UIkit.modal.prompt($hb.language._('backup-restore-prompt')).then(function (text) {
                    if ('RESTORE' === text) {
                        UIkit.notification('<span uk-spinner></span> ' + $hb.language._('please-wait-msg'), {timeout: 30000});
                        $.http.post(el.data('url'), {backupFile: el.data('file')}, function (response) {
                            UIkit.notification.closeAll();

                            if (response === true) {
                                UIkit.notification('<span uk-spinner></span> ' + $hb.language._('backup-restore-warning'), {
                                    status: 'warning',
                                    timeout: 90000,
                                });
                            }
                        });
                    }
                });
            },
            function () {
            }
        );
    });
});
