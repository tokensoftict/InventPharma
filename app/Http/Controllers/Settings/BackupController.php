<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Backup\BackupActions;
use App\Services\Backup\Exceptions\BackupException;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Backup Manager endpoints. Pages render Livewire components (same pattern as the other
 * Settings controllers); the action endpoints exist so each capability has its own
 * route-name permission task and can also be used programmatically. All real work and
 * authorization lives in BackupActions.
 */
class BackupController extends Controller
{
    public function __construct(protected BackupActions $actions)
    {
    }

    /* ---------------------------------------------------------------- pages */

    public function index()
    {
        return setPageContent('settings.backup.index');
    }

    public function history()
    {
        return setPageContent('settings.backup.history');
    }

    public function settings()
    {
        return setPageContent('settings.backup.settings');
    }

    /* -------------------------------------------------------------- actions */

    public function run(Request $request): JsonResponse
    {
        return $this->json(function () use ($request) {
            $backup = $this->actions->backupNow((int) $request->input('destination_id'));

            return ['message' => 'Backup started.', 'backup_id' => $backup->id, 'status' => $backup->status];
        });
    }

    public function download(int $id)
    {
        try {
            return $this->actions->download($id);
        } catch (AuthorizationException) {
            abort(403);
        } catch (BackupException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function delete(int $id): JsonResponse
    {
        return $this->json(function () use ($id) {
            $this->actions->delete($id);

            return ['message' => 'Backup deleted.'];
        });
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        return $this->json(function () use ($request, $id) {
            $this->actions->restore($id, (string) $request->input('confirmation'), $request->input('password'));

            return ['message' => 'Restore started. It runs in the background.'];
        });
    }

    public function test(int $id): JsonResponse
    {
        return $this->json(fn () => ['message' => $this->actions->testDestination($id)]);
    }

    public function configure(Request $request): JsonResponse
    {
        return $this->json(function () use ($request) {
            $this->actions->saveSettings($request->all());

            return ['message' => 'Backup settings saved.'];
        });
    }

    /* ---------------------------------------------------------- google drive */

    public function googleConnect(Request $request): RedirectResponse
    {
        try {
            return redirect()->away($this->actions->googleConnectUrl((string) $request->query('folder', 'Inventory Backups')));
        } catch (\Throwable $e) {
            return redirect()->route('backup.settings')->with('backup_error', $this->message($e));
        }
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        try {
            $this->actions->googleCallback($request->query('code'), $request->query('state'), $request->query('error'));

            return redirect()->route('backup.settings')->with('backup_success', 'Google Drive connected.');
        } catch (\Throwable $e) {
            return redirect()->route('backup.settings')->with('backup_error', $this->message($e));
        }
    }

    public function googleDisconnect(int $id): JsonResponse
    {
        return $this->json(function () use ($id) {
            $this->actions->googleDisconnect($id);

            return ['message' => 'Google Drive disconnected.'];
        });
    }

    /* -------------------------------------------------------------- helpers */

    protected function json(Closure $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (BackupException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => $e->errorCode], 422);
        }
    }

    protected function message(\Throwable $e): string
    {
        return $e instanceof BackupException || $e instanceof AuthorizationException ? $e->getMessage() : 'Something went wrong. See the backup log for details.';
    }
}
