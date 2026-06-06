#!/bin/bash
# Backup automatique de la base SQLite du POS
# À planifier via crontab : 59 23 * * * /home/suicide/pos-system/backup.sh

BACKUP_DIR="/home/suicide/backups/pos"
DB_FILE="/home/suicide/pos-system/database/database.sqlite"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Créer le dossier de backup si nécessaire
mkdir -p "$BACKUP_DIR"

# Backup avec timestamp
cp "$DB_FILE" "$BACKUP_DIR/pos_$DATE.sqlite"

# Vérifier l'intégrité du backup
if sqlite3 "$BACKUP_DIR/pos_$DATE.sqlite" "PRAGMA integrity_check;" | grep -q "ok"; then
    echo "[$(date)] Backup OK: pos_$DATE.sqlite"
else
    echo "[$(date)] ERREUR: Backup corrompu!"
    rm -f "$BACKUP_DIR/pos_$DATE.sqlite"
    exit 1
fi

# Supprimer les backups de plus de 30 jours
find "$BACKUP_DIR" -name "pos_*.sqlite" -mtime +$RETENTION_DAYS -delete

# Afficher la taille du dossier de backup
du -sh "$BACKUP_DIR"
