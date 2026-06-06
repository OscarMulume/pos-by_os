<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hors ligne — POS Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        .container { max-width: 400px; }
        .icon { font-size: 64px; margin-bottom: 1.5rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; }
        p { color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6; }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #1e293b;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; }
        .status-dot.online { background: #22c55e; }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #f59e0b;
            color: #0f172a;
            font-weight: 600;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn:hover { background: #d97706; }
        .offline-orders {
            margin-top: 2rem;
            padding: 1rem;
            background: #1e293b;
            border-radius: 0.75rem;
            text-align: left;
        }
        .offline-orders h2 { font-size: 0.875rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .offline-orders .count { font-size: 2rem; font-weight: 700; color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📡</div>
        <h1>Mode Hors Ligne</h1>
        <p>Vous n'êtes pas connecté à Internet. Les ventes sont sauvegardées localement et seront synchronisées automatiquement.</p>
        <div class="status">
            <span class="status-dot" id="statusDot"></span>
            <span id="statusText">Hors ligne</span>
        </div>
        <div class="offline-orders">
            <h2>Ventes en attente de sync</h2>
            <div class="count" id="offlineCount">0</div>
        </div>
        <br><br>
        <a href="/pos" class="btn">Retourner au POS</a>
    </div>
    <script>
        // Check online status
        function updateStatus() {
            const dot = document.getElementById('statusDot');
            const text = document.getElementById('statusText');
            if (navigator.onLine) {
                dot.classList.add('online');
                text.textContent = 'En ligne';
            } else {
                dot.classList.remove('online');
                text.textContent = 'Hors ligne';
            }
        }
        updateStatus();
        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);

        // Count offline orders
        if (window.indexedDB) {
            const req = indexedDB.open('pos_offline_db', 1);
            req.onsuccess = e => {
                const db = e.target.result;
                if (db.objectStoreNames.contains('orders')) {
                    const tx = db.transaction('orders', 'readonly');
                    const store = tx.objectStore('orders');
                    store.count().onsuccess = e => {
                        document.getElementById('offlineCount').textContent = e.target.result;
                    };
                }
            };
        }
    </script>
</body>
</html>
