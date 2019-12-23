if (window.XMLHttpRequest) {
    var
        backupList = document.getElementById('backup-item-list'),
        btnBackup = document.getElementById('btn-backup-system'),
        disableButtons = function () {
            btnBackup.disabled = true;
            backupList.querySelectorAll('.btn-backup').forEach(function (btn) {
                btn.disabled = true;
            });
        },
        enableButtons = function () {
            btnBackup.disabled = false;
            backupList.querySelectorAll('.btn-backup').forEach(function (btn) {
                btn.disabled = false;
            });
        },
        appendMessage = function (messageContainer, message, end) {
            end = end || false;
            message = message.split('###');
            message = JSON.parse(message[message.length - 1].replace(/\}.*$/gi, '}'));

            if (true === end) {
                return message.message;
            }

            messageContainer.querySelectorAll('.uk-text-warning').forEach(function (element) {
                element.classList.remove('uk-text-warning');
                clearInterval(element.dataset.interval);
            });

            messageContainer.innerHTML = messageContainer.innerHTML + '<div class="uk-text-' + message.status + '">' + message.message + '</div>';
            var element = messageContainer.querySelector('.uk-text-warning');

            if (element) {
                element.dataset.interval = setInterval(function () {
                    element.style.display = element.style.display === 'none' ? 'block' : 'none';
                }, 700);
            }
        },
        removeBackup = function (element) {
            UIkit.modal
                .confirm(cmsCore.language._('backup-remove-confirm', {file: element.getAttribute('data-file')}))
                .then(
                    function () {
                        var xhr = new window.XMLHttpRequest;
                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === XMLHttpRequest.DONE) {
                                UIkit.notification(JSON.parse(xhr.response));
                                backupList.removeChild(element.parentElement);
                            }
                        };
                        xhr.open('POST', element.getAttribute('data-url'), true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.send('backupFile=' + element.getAttribute('data-file'));
                    },
                    function () {

                    }
                );
        },
        restoreBackup = function (element) {
            UIkit.modal
                .prompt(cmsCore.language._('backup-restore-confirm', {file: element.getAttribute('data-file')}), '')
                .then(function (confirmText) {
                    if ('YES' !== confirmText) {
                        return false;
                    }

                    var
                        xhr = new window.XMLHttpRequest,
                        messageContainer = document.createElement('div');
                    btnBackup.parentElement.appendChild(messageContainer);
                    disableButtons();
                    xhr.onreadystatechange = function () {
                        appendMessage(messageContainer, xhr.response);

                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            setTimeout(function () {
                                location.reload();
                            }, 500);
                        }
                    };

                    xhr.open('POST', element.getAttribute('data-url'), true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.send('backupFile=' + element.getAttribute('data-file'));
                });
        },
        initElementEvent = function (element) {
            element.addEventListener('click', function () {
                if (element.classList.contains('btn-backup-remove')) {
                    removeBackup(element);
                } else {
                    restoreBackup(element);
                }
            });
        };
    btnBackup.addEventListener('click', function () {
        var
            xhr = new window.XMLHttpRequest,
            messageContainer = document.createElement('div');
        btnBackup.parentElement.appendChild(messageContainer);
        disableButtons();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.LOADING) {
                appendMessage(messageContainer, xhr.response);
            }

            if (xhr.readyState === XMLHttpRequest.DONE) {
                backupList.innerHTML += appendMessage(messageContainer, xhr.response, true);
                backupList.querySelectorAll('li:last-child > .btn-backup').forEach(function (element) {
                    initElementEvent(element);
                });

                setTimeout(function () {
                    enableButtons();
                    messageContainer.parentElement.removeChild(messageContainer);
                }, 3500);
            }
        };

        xhr.open('POST', btnBackup.getAttribute('data-url'), true);
        xhr.setRequestHeader('Content-type', 'text/html; charset=UTF-8');
        xhr.send(null);
    });

    backupList.querySelectorAll('.btn-backup').forEach(function (element) {
        initElementEvent(element);
    });

} else {
    alert('Your browser\'s not support XMLHttpRequest');
}

