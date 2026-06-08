<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DeployController extends Controller
{
    /**
     * Webhook GitHub — Déploiement automatique
     * URL: https://votre-app.com/webhook/deploy
     */
    public function webhook(Request $request)
    {
        // Vérifier le secret GitHub
        $secret = config('app.deploy_secret', 'msec-pos-2026');
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $computed = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($computed, $signature ?? '')) {
            Log::warning('Webhook deploy: signature invalide');
            abort(403, 'Signature invalide');
        }

        // Vérifier que c'est un push sur main
        $data = json_decode($payload, true);
        if (($data['ref'] ?? '') !== 'refs/heads/main') {
            return response()->json(['message' => 'Ignoré — pas sur main']);
        }

        // Exécuter le déploiement
        Log::info('Webhook deploy: démarrage du déploiement');

        $process = new Process(['bash', base_path('deploy.sh')]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('Webhook deploy: échec — ' . $process->getErrorOutput());
            return response()->json(['error' => $process->getErrorOutput()], 500);
        }

        Log::info('Webhook deploy: succès');
        return response()->json(['message' => 'Déploiement réussi', 'output' => $process->getOutput()]);
    }
}
