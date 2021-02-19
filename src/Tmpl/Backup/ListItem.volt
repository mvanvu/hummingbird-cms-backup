<li>
    {{ backupFile }}
    <button class="uk-icon-nav btn-backup btn-backup-remove" uk-icon="icon: trash" type="button" uk-tooltip
            title="{{ _('backup-remove') | escape_attr }}" data-file="{{ backupFile | escape_attr }}"
            data-url="{{ route('backup/remove') | escape_attr }}"></button>
    &nbsp;|&nbsp;
    <button class="uk-icon-nav btn-backup btn-backup-restore" uk-icon="icon: refresh" type="button" uk-tooltip
            title="{{ _('backup-restore') | escape_attr }}" data-file="{{ backupFile | escape_attr }}"
            data-url="{{ route('backup/restore') | escape_attr }}"></button>
</li>