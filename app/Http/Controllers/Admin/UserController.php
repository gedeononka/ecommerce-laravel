<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('deleted_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNotNull('deleted_at');
            }
        }
        
        // Filter by registration date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $users = $query->withTrashed()->latest()->paginate(20);
        
        // Calculate statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereNull('deleted_at')->count(),
            'inactive_users' => User::onlyTrashed()->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'client_users' => User::where('role', 'client')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
        ];
        
        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,client',
            'password' => 'required|min:8|confirmed',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'email_verified_at' => now(), // Auto-verify admin created users
        ]);
        
        return redirect()->route('admin.users.index')
                        ->with('success', 'Utilisateur créé avec succès');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load('orders');
        
        // Get user statistics
        $stats = [
            'total_orders' => $user->orders->count(),
            'completed_orders' => $user->orders->where('status', 'completed')->count(),
            'pending_orders' => $user->orders->where('status', 'pending')->count(),
            'cancelled_orders' => $user->orders->where('status', 'cancelled')->count(),
            'total_spent' => $user->orders->where('status', 'completed')->sum('total_amount'),
            'average_order_value' => $user->orders->where('status', 'completed')->avg('total_amount'),
            'last_order_date' => $user->orders->max('created_at'),
        ];
        
        $recentOrders = $user->orders()->with('orderItems.product')->latest()->limit(5)->get();
        
        return view('admin.users.show', compact('user', 'stats', 'recentOrders'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,client',
            'password' => 'nullable|min:8|confirmed',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role = $request->role;
        $user->address = $request->address;
        $user->city = $request->city;
        $user->postal_code = $request->postal_code;
        $user->country = $request->country;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        return redirect()->route('admin.users.index')
                        ->with('success', 'Utilisateur mis à jour avec succès');
    }

    /**
     * Remove the specified user (soft delete)
     */
    public function destroy(User $user)
    {
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Impossible de supprimer le dernier administrateur');
        }
        
        $user->delete();
        
        return redirect()->route('admin.users.index')
                        ->with('success', 'Utilisateur supprimé avec succès');
    }

    /**
     * Restore soft deleted user
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        
        return redirect()->back()->with('success', 'Utilisateur restauré avec succès');
    }

    /**
     * Force delete user
     */
    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Impossible de supprimer définitivement le dernier administrateur');
        }
        
        $user->forceDelete();
        
        return redirect()->back()->with('success', 'Utilisateur supprimé définitivement');
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        if ($user->trashed()) {
            $user->restore();
            $message = 'Utilisateur activé avec succès';
        } else {
            if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
                return redirect()->back()->with('error', 'Impossible de désactiver le dernier administrateur');
            }
            $user->delete();
            $message = 'Utilisateur désactivé avec succès';
        }
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Send password reset link
     */
    public function sendPasswordReset(User $user)
    {
        // Logic to send password reset email
        // This would typically use Laravel's built-in password reset functionality
        
        return redirect()->back()->with('success', 'Lien de réinitialisation du mot de passe envoyé');
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $query = User::query();
        
        // Apply same filters as index
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $users = $query->get();
        
        $csvData = $this->generateCSV($users);
        
        return response()->streamDownload(function() use ($csvData) {
            echo $csvData;
        }, 'users-' . date('Y-m-d') . '.csv');
    }

    /**
     * Generate CSV data for users
     */
    private function generateCSV($users)
    {
        $csvData = "ID,Nom,Email,Téléphone,Rôle,Date d'inscription,Statut\n";
        
        foreach ($users as $user) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $user->id,
                $user->name,
                $user->email,
                $user->phone ?? '',
                $user->role,
                $user->created_at->format('Y-m-d H:i:s'),
                $user->deleted_at ? 'Inactif' : 'Actif'
            );
        }
        
        return $csvData;
    }

    /**
     * Bulk actions for users
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,restore,activate,deactivate,export',
            'users' => 'required|array|min:1',
            'users.*' => 'exists:users,id'
        ]);
        
        $users = User::withTrashed()->whereIn('id', $request->users);
        
        switch ($request->action) {
            case 'delete':
                $users->get()->each(function($user) {
                    if ($user->role !== 'admin' || User::where('role', 'admin')->count() > 1) {
                        $user->delete();
                    }
                });
                $message = 'Utilisateurs supprimés avec succès';
                break;
                
            case 'restore':
                $users->restore();
                $message = 'Utilisateurs restaurés avec succès';
                break;
                
            case 'activate':
                $users->restore();
                $message = 'Utilisateurs activés avec succès';
                break;
                
            case 'deactivate':
                $users->get()->each(function($user) {
                    if ($user->role !== 'admin' || User::where('role', 'admin')->count() > 1) {
                        $user->delete();
                    }
                });
                $message = 'Utilisateurs désactivés avec succès';
                break;
                
            case 'export':
                $usersList = $users->get();
                $csvData = $this->generateCSV($usersList);
                
                return response()->streamDownload(function() use ($csvData) {
                    echo $csvData;
                }, 'selected-users-' . date('Y-m-d') . '.csv');
        }
        
        return redirect()->back()->with('success', $message);
    }
}