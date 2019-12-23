<div class="uk-margin" id="backup-container">
    <button class="uk-button uk-button-primary" id="btn-backup-system" type="button"
            data-url="{{ route('backup/backup') | escape_attr }}">
        {{ _('backup-run') }}
    </button>
    <ul class="uk-list uk-list-divider uk-text-meta" id="backup-item-list">
        {% if backupFiles | length %}
            {% for backupFile in backupFiles %}
                {{ partial('Backup/ListItem', ['backupFile': backupFile]) }}
            {% endfor %}
        {% endif %}
    </ul>
</div>