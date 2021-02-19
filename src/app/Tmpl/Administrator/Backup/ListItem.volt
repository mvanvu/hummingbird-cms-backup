<li>
    <div class="uk-grid-small uk-grid-divider" uk-grid>
        <div class="uk-text-emphasis">
            {{ backupFile }}
        </div>
        <button class="uk-icon-nav btn-backup btn-backup-remove uk-text-danger" uk-icon="icon: trash" type="button"
                data-file="{{ backupFile | escape_attr }}"
                data-url="{{ route('backup/remove') | escape_attr }}">
            {{ _('backup-remove') }}
        </button> &nbsp;
        <button class="uk-icon-nav btn-backup btn-backup-restore uk-text-warning" uk-icon="icon: refresh" type="button"
                data-file="{{ backupFile | escape_attr }}"
                data-url="{{ route('backup/restore') | escape_attr }}">
            {{ _('backup-restore') }}
        </button>
    </div>

</li>