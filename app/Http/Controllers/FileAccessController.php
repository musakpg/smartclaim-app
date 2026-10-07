<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileAccessController extends Controller
{
    /**
     * Authorize and serve private files securely.
     *
     * @param string $folder
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveFile($folder, $filename)
    {
        // Enforce authentication
        if (!Auth::check()) {
            abort(403, 'Unauthorized access.');
        }

        // Prevent directory traversal attacks
        if (str_contains($folder, '..') || str_contains($filename, '..') || str_contains($folder, '\\') || str_contains($filename, '\\')) {
            abort(403, 'Invalid file path traversal attempt.');
        }

        $cleanFolder = trim($folder, '/');
        $cleanFilename = trim($filename, '/');
        $path = $cleanFolder . '/' . $cleanFilename;

        $currentUser = Auth::user();
        $currentUserId = $currentUser->user_id ?? $currentUser->id;
        $normalizedRole = strtolower(trim($currentUser->role ?? ''));
        $isPrivileged = in_array($normalizedRole, ['manager', 'finance', 'fin']);

        // Authorize: Only the document/claim owner, Finance auditors, or Managers can access files
        if (!$isPrivileged) {
            $baseName = basename($cleanFilename);

            $ownsClaim = Claim::where('user_id', $currentUserId)
                ->where(function ($query) use ($path, $baseName) {
                    $query->where('receipt_image_path', $path)
                        ->orWhere('payment_proof_path', $path)
                        ->orWhere('receipt_image_path', 'like', "%{$baseName}")
                        ->orWhere('payment_proof_path', 'like', "%{$baseName}");
                })
                ->exists();

            $ownsVehicle = Vehicle::where('user_id', $currentUserId)
                ->where(function ($query) use ($path, $baseName) {
                    $query->where('grant_document_path', $path)
                        ->orWhere('roadtax_document_path', $path)
                        ->orWhere('grant_document_path', 'like', "%{$baseName}")
                        ->orWhere('roadtax_document_path', 'like', "%{$baseName}");
                })
                ->exists();

            if (!$ownsClaim && !$ownsVehicle) {
                abort(403, 'Unauthorized access to this private document.');
            }
        }

        // Verify physical presence on storage disk
        if (Storage::disk('private')->exists($path)) {
            return response()->file(Storage::disk('private')->path($path));
        }

        // Legacy fallback for documents previously stored in public disk
        if (Storage::disk('public')->exists($path)) {
            return response()->file(Storage::disk('public')->path($path));
        }

        abort(404, 'File not found on storage disk.');
    }
}
