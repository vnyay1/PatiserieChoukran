<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste des notifications de l'utilisateur
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filtre par type
        if ($request->has('type')) {
            $query->type($request->type);
        }

        // Filtre par statut de lecture
        if ($request->has('est_lu')) {
            if ($request->boolean('est_lu')) {
                $query->lu();
            } else {
                $query->nonLu();
            }
        }

        $notifications = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->marquerCommeLu();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'data' => $notification,
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('est_lu', false)
            ->update([
                'est_lu' => true,
                'date_lecture' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ]);
    }

    /**
     * Nombre de notifications non lues
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->nonLu()
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function clearRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('est_lu', true)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifications lues supprimées',
        ]);
    }
}
